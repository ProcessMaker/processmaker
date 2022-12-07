<?php

namespace Tests\Feature\ImportExport\Exporters;

use Database\Seeders\CategorySystemSeeder;
use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ScriptExporterTest extends TestCase
{
    use HelperTrait;

    public function test()
    {
        DB::beginTransaction();
        $category = ScriptCategory::factory()->create(['name' => 'test category']);
        $scriptUser = User::factory()->create(['username' => 'scriptuser']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php return [];',
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
}
