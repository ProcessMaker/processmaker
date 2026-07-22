<?php

namespace Tests\Feature\ImportExport\Exporters;

use Database\Seeders\CategorySystemSeeder;
use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use ReflectionClass;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ScriptExporterTest extends TestCase
{
    use HelperTrait;

    /**
     *  Init admin user
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminUser();
    }

    public function test()
    {
        DB::beginTransaction();
        $environmentVariable1 = EnvironmentVariable::factory()->create(['name' => 'MY_VAR_1']);
        $environmentVariable2 = EnvironmentVariable::factory()->create(['name' => 'MY_VAR_2']);
        $environmentVariable3 = EnvironmentVariable::factory()->create(['name' => 'MY_VAR_3']);
        $category = ScriptCategory::factory()->create(['name' => 'test category']);
        $scriptUser = User::factory()->create(['username' => 'scriptuser']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php $var1 = getenv(\'MY_VAR_1\'); $var2 = getenv(\'MY_VAR_2\') return [];',
            'run_as_user_id' => $scriptUser->id,
        ]);
        $script->categories()->sync($category);

        $payload = $this->export($script, ScriptExporter::class);
        DB::rollBack(); // Delete all created items since DB::beginTransaction
        $this->assertEquals(0, Script::where('title', 'test')->count());
        $this->import($payload);

        $script = Script::where('title', 'test')->firstOrFail();
        $this->assertEquals('test category', $script->categories[0]->name);
        $this->assertEquals('scriptuser', $script->runAsUser->username);
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable1->name]);
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable2->name]);
        $this->assertDatabaseMissing('environment_variables', ['name' => $environmentVariable3->name]);
    }

    public function testExportUncategorized()
    {
        DB::beginTransaction();
        (new CategorySystemSeeder)->run();
        $uncategorizedCategory = \ProcessMaker\Models\ScriptCategory::first();

        $script = Script::factory()->create(['title' => 'test']);
        $script->categories()->sync([$uncategorizedCategory->id]);
        $payload = $this->export($script, ScriptExporter::class);
        DB::rollBack(); // Delete all created items since DB::beginTransaction

        (new CategorySystemSeeder)->run();
        $existingUncategorizedCategory = \ProcessMaker\Models\ScriptCategory::first();
        $existingUuid = $existingUncategorizedCategory->uuid;

        $this->import($payload);

        $script = Script::where('title', 'test')->firstOrFail();
        $this->assertEquals($script->categories->first()->uuid, $existingUuid);
    }

    public function testHiddenUsesParentMode()
    {
        $scriptCategory1 = ScriptCategory::factory()->create(['name' => 'test category A']);
        $scriptCategory2 = ScriptCategory::factory()->create(['name' => 'test category B']);
        $script = Script::factory()->create([
            'title' => 'test',
            'script_category_id' => $scriptCategory1->id . ',' . $scriptCategory2->id,
        ]);
        $originalCategoryCount = ScriptCategory::count();

        $payload = $this->export($script, ScriptExporter::class);

        $options = new Options([
            $script->uuid => ['mode' => 'update'],
        ]);
        $this->import($payload, $options);

        // Assert nothing changed
        $this->assertEquals($originalCategoryCount, ScriptCategory::count());
        $this->assertDatabaseHas('script_categories', ['name' => 'test category A']);
        $this->assertDatabaseHas('script_categories', ['name' => 'test category B']);

        $options = new Options([
            $script->uuid => ['mode' => 'copy'],
            $scriptCategory1->uuid => ['mode' => 'update'], // should be ignored and set to copy
            $scriptCategory2->uuid => ['mode' => 'update'], // should be ignored and set to copy
        ]);
        $this->import($payload, $options);

        // Check originals are unchanged
        $this->assertEquals('test', $script->refresh()->title);
        $this->assertEquals(2, $script->categories->count());
        $category1 = $script->categories[0];
        $category2 = $script->categories[1];
        $this->assertEquals('test category A', $category1->name);
        $this->assertEquals('test category B', $category2->name);

        // Check copied script has new copied categories
        $newScript = Script::where('title', 'test 2')->firstOrFail();
        $newCategory1 = $newScript->categories[0];
        $newCategory2 = $newScript->categories[1];
        $this->assertEquals(2, $newScript->categories->count());
        $this->assertEquals('test category A 2', $newCategory1->name);
        $this->assertEquals('test category B 2', $newCategory2->name);
    }

    public function testNoMatchingRunAsUser()
    {
        DB::beginTransaction();
        $user = User::factory()->create(['username' => 'test']);
        $admin_user = User::where('is_administrator', true)->first();
        $script = Script::factory()->create(['title' => 'test', 'run_as_user_id' => $user->id]);

        $payload = $this->export($script, ScriptExporter::class, null, false);
        DB::rollBack(); // Delete all created items since DB::beginTransaction

        $this->import($payload);

        $script = Script::where('title', 'test')->firstOrFail();
        $this->assertEquals($script->run_as_user_id, $admin_user->id);
    }

    public function testRunAsUserIdNull()
    {
        DB::beginTransaction();
        $script = Script::factory()->create(['title' => 'test', 'run_as_user_id' => null]);

        $payload = $this->export($script, ScriptExporter::class, null, false);
        DB::rollBack(); // Delete all created items since DB::beginTransaction

        $this->import($payload);

        $script = Script::where('title', 'test')->firstOrFail();
        $this->assertNull($script->run_as_user_id);
    }

    /**
     * Test that the environment variables are duplicated when they are used in the script
     * and the import options are set to create a copy
     */
    public function testWithDuplicatedEnvVariable()
    {
        $environmentVariable1 = EnvironmentVariable::factory()->create(['name' => 'AWS_ACCESS_KEY_ID']);
        $environmentVariable2 = EnvironmentVariable::factory()->create(['name' => 'AWS_SECRET_ACCESS_KEY']);
        $environmentVariable3 = EnvironmentVariable::factory()->create(['name' => 'MY_VAR']);

        $category = ScriptCategory::factory()->create(['name' => 'test category']);
        $scriptUser = User::factory()->create(['username' => 'scriptuser']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php $var1 = getenv(\'AWS_ACCESS_KEY_ID\'); $var2 = getenv(\'AWS_SECRET_ACCESS_KEY\') return [];',
            'run_as_user_id' => $scriptUser->id,
        ]);
        $script->categories()->sync($category);

        $payload = $this->export($script, ScriptExporter::class);
        $options = new Options([
            $environmentVariable1->uuid => ['mode' => 'copy'],
            $environmentVariable2->uuid => ['mode' => 'copy'],
            $environmentVariable3->uuid => ['mode' => 'copy'],
        ]);
        $this->import($payload, $options);

        $script = Script::where('title', 'test')->firstOrFail();
        $this->assertEquals('test category', $script->categories[0]->name);
        $this->assertEquals('scriptuser', $script->runAsUser->username);

        // The original environment variables should be present
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable1->name]);
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable2->name]);
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable3->name]);

        // The duplicated environment variables should be suffixed with _2
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable1->name . '_2']);
        $this->assertDatabaseHas('environment_variables', ['name' => $environmentVariable2->name . '_2']);
        $this->assertDatabaseMissing('environment_variables', ['name' => $environmentVariable3->name . '_2']);
    }

    public function testLinkedAssetRemappedOnImportUpdateMode()
    {
        $screen = Screen::factory()->create(['title' => 'Linked Screen']);
        $environmentVariable = EnvironmentVariable::factory()->create([
            'name' => 'MY_SCREEN_ID',
            'description' => 'Screen id for scripts',
            'asset_type' => Screen::class,
            'value' => (string) $screen->id,
        ]);
        $script = Script::factory()->create([
            'title' => 'script with linked env var',
            'code' => '<?php $screenId = getenv(\'MY_SCREEN_ID\'); return [];',
        ]);

        $payload = $this->export($script, ScriptExporter::class);

        $envVarPayload = collect($payload['export'])->first(function ($asset) {
            return ($asset['model'] ?? null) === EnvironmentVariable::class;
        });
        $this->assertNotNull($envVarPayload);
        $this->assertTrue(
            collect($envVarPayload['dependents'])->pluck('type')->contains(DependentType::ENVIRONMENT_VARIABLE_ASSET)
        );

        // Simulate a stale numeric ID on the target instance before update import.
        DB::table('environment_variables')
            ->where('id', $environmentVariable->id)
            ->update(['value' => encrypt('999999')]);
        $this->assertEquals('999999', $environmentVariable->fresh()->value);

        $options = new Options([
            $script->uuid => ['mode' => 'update'],
            $environmentVariable->uuid => ['mode' => 'update'],
            $screen->uuid => ['mode' => 'update'],
        ]);
        $this->import($payload, $options);

        $screen->refresh();
        $environmentVariable->refresh();

        $this->assertEquals(1, Screen::where('title', 'Linked Screen')->count());
        $this->assertEquals(1, EnvironmentVariable::where('name', 'MY_SCREEN_ID')->count());
        $this->assertEquals(Screen::class, $environmentVariable->asset_type);
        $this->assertEquals((string) $screen->id, $environmentVariable->value);
    }

    public function testLinkedAssetRemappedOnImportCopyMode()
    {
        $screen = Screen::factory()->create(['title' => 'Linked Screen']);
        $environmentVariable = EnvironmentVariable::factory()->create([
            'name' => 'MY_SCREEN_ID',
            'description' => 'Screen id for scripts',
            'asset_type' => Screen::class,
            'value' => (string) $screen->id,
        ]);
        $script = Script::factory()->create([
            'title' => 'script with linked env var',
            'code' => '<?php $screenId = getenv(\'MY_SCREEN_ID\'); return [];',
        ]);

        $originalScreenUuid = $screen->uuid;
        $originalScreenId = $screen->id;
        $originalEnvUuid = $environmentVariable->uuid;

        $payload = $this->export($script, ScriptExporter::class);

        $options = new Options([
            $script->uuid => ['mode' => 'copy'],
            $environmentVariable->uuid => ['mode' => 'copy'],
            $screen->uuid => ['mode' => 'copy'],
        ]);
        $this->import($payload, $options);

        // Originals remain unchanged.
        $screen->refresh();
        $environmentVariable->refresh();
        $this->assertEquals($originalScreenUuid, $screen->uuid);
        $this->assertEquals((string) $originalScreenId, $environmentVariable->value);
        $this->assertEquals(Screen::class, $environmentVariable->asset_type);
        $this->assertEquals($originalEnvUuid, $environmentVariable->uuid);

        $copiedScreen = Screen::where('title', 'Linked Screen 2')->firstOrFail();
        $copiedVariable = EnvironmentVariable::where('name', 'MY_SCREEN_ID_2')->firstOrFail();

        $this->assertNotEquals($originalScreenUuid, $copiedScreen->uuid);
        $this->assertNotEquals($originalEnvUuid, $copiedVariable->uuid);
        $this->assertEquals(Screen::class, $copiedVariable->asset_type);
        $this->assertEquals((string) $copiedScreen->id, $copiedVariable->value);
    }

    /**
     * Scenario A: linked asset is discarded but already exists on target — remap value to that asset.
     */
    public function testLinkedAssetRemappedWhenDiscardedButExistsOnTarget()
    {
        $screen = Screen::factory()->create(['title' => 'Existing Linked Screen']);
        $environmentVariableName = 'MY_SCREEN_ID';
        $environmentVariable = EnvironmentVariable::factory()->create([
            'name' => $environmentVariableName,
            'description' => 'Screen id for scripts',
            'asset_type' => Screen::class,
            'value' => (string) $screen->id,
        ]);
        $script = Script::factory()->create([
            'title' => 'script with linked env var discard exists',
            'code' => '<?php $screenId = getenv(\'MY_SCREEN_ID\'); return [];',
        ]);

        $payload = $this->export($script, ScriptExporter::class);

        // Stale value before import; asset itself stays and is discarded (not updated).
        DB::table('environment_variables')
            ->where('id', $environmentVariable->id)
            ->update(['value' => encrypt('999999')]);

        $options = new Options([
            $script->uuid => ['mode' => 'update'],
            $environmentVariable->uuid => ['mode' => 'update'],
            $screen->uuid => ['mode' => 'discard'],
        ]);
        $this->import($payload, $options);

        $screen->refresh();
        // Get the environment variable by name after import
        $environmentVariable = EnvironmentVariable::where('name', $environmentVariableName)->firstOrFail();

        $this->assertEquals(1, Screen::whereLike('title', 'Existing Linked Screen%')->count());
        $this->assertEquals(Screen::class, $environmentVariable->asset_type);
        $this->assertEquals((string) $screen->id, $environmentVariable->value);
    }

    /**
     * Scenario B: linked asset is missing on target — clear link and value, warn in import summary.
     */
    public function testLinkedAssetClearedWhenMissingOnImport()
    {
        $screen = Screen::factory()->create(['title' => 'Missing Linked Screen']);
        $environmentVariableName = 'MY_SCREEN_ID';
        $environmentVariable = EnvironmentVariable::factory()->create([
            'name' => $environmentVariableName,
            'description' => 'Screen id for scripts',
            'asset_type' => Screen::class,
            'value' => (string) $screen->id,
        ]);
        $script = Script::factory()->create([
            'title' => 'script with linked env var missing asset',
            'code' => '<?php $screenId = getenv(\'MY_SCREEN_ID\'); return [];',
        ]);

        $payload = $this->export($script, ScriptExporter::class);
        $screenUuid = $screen->uuid;

        // Remove the asset from the target instance before import.
        $screen->delete();
        $this->assertNull(Screen::where('uuid', $screenUuid)->first());

        $logger = new Logger();
        $options = new Options([
            $script->uuid => ['mode' => 'update'],
            $environmentVariable->uuid => ['mode' => 'update'],
            $screenUuid => ['mode' => 'discard'],
        ]);
        $this->import($payload, $options, $logger);

        // Get the environment variable by name after import
        $environmentVariable = EnvironmentVariable::where('name', $environmentVariableName)->firstOrFail();

        $this->assertNull($environmentVariable->asset_type);
        $this->assertSame('', $environmentVariable->value);

        $warnings = $this->getLoggerWarnings($logger);
        $expectedWarning = __(
            'Asset linked to environment variable ":env_variable" was missing on import; link and value were cleared',
            ['env_variable' => 'MY_SCREEN_ID']
        );
        $this->assertContains($expectedWarning, $warnings);
    }

    private function getLoggerWarnings(Logger $logger): array
    {
        $reflection = new ReflectionClass($logger);
        $property = $reflection->getProperty('warnings');
        $property->setAccessible(true);

        return $property->getValue($logger);
    }
}
