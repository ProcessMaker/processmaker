<?php

namespace Tests\Feature\ImportExport\Exporters;

use Database\Seeders\CategorySystemSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\ExpectationFailedException;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
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
    }}
