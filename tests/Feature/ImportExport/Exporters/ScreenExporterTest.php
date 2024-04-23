<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ScreenExporterTest extends TestCase
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

    private function fixtures()
    {
        $screen = $this->createScreen('screen_with_nested_screen', ['title' => 'screen'], 'watchers');
        $screenCategory1 = ScreenCategory::factory()->create(['name' => 'category 1', 'status' => 'ACTIVE']);
        $screenCategory2 = ScreenCategory::factory()->create(['name' => 'category 2', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;

        $script = Script::factory()->create(['title' => 'script']);
        $this->associateScriptWatcher($screen, $script);

        $nestedScreen = $this->createScreen('screen_with_nested_screen', ['title' => 'nested screen']);
        $nestedScreen->screen_category_id = $screenCategory1->id;
        $nestedNestedScreen = Screen::factory()->create(['title' => 'nested nested screen', 'config' => []]);
        $nestedNestedScreen->screen_category_id = $screenCategory2->id;
        $this->associateNestedScreen($nestedScreen, $nestedNestedScreen);
        $this->associateNestedScreen($screen, $nestedScreen);

        $screen->save();

        return [$screen, $screenCategory1, $screenCategory2, $script, $nestedScreen, $nestedNestedScreen];
    }

    public function testExport()
    {
        list($screen, $screenCategory1, $screenCategory2, $script, $nestedScreen, $nestedNestedScreen) =
            $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $screenDependents = Arr::get($payload, 'export.' . $screen->uuid . '.dependents');
        $this->assertCount(4, $screenDependents);
        $screenDependentUuids = Arr::pluck($screenDependents, 'uuid');
        $this->assertContains($screenCategory1->uuid, $screenDependentUuids);
        $this->assertContains($screenCategory2->uuid, $screenDependentUuids);
        $this->assertContains($script->uuid, $screenDependentUuids);

        $scriptDependents = Arr::get($payload, 'export.' . $script->uuid . '.dependents');
        $scriptDependentUuids = Arr::pluck($scriptDependents, 'uuid');
        $this->assertContains($script->category->uuid, $scriptDependentUuids);

        $this->assertContains($nestedScreen->uuid, $screenDependentUuids);

        $nestedScreenDependents = Arr::get($payload, 'export.' . $nestedScreen->uuid . '.dependents');
        $nestedScreenDependentUuids = Arr::pluck($nestedScreenDependents, 'uuid');

        $this->assertContains($nestedNestedScreen->uuid, $nestedScreenDependentUuids);
        $this->assertContains($screenCategory1->uuid, $nestedScreenDependentUuids);
    }

    public function testImport()
    {
        list($screen, $screenCategory1, $screenCategory2, $script, $nestedScreen, $nestedNestedScreen) =
            $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $screen->delete();
        $script->delete();
        $nestedScreen->delete();
        $screenCategory1->update(['name' => 'category name old']);
        $screenCategory2->delete();
        $nestedNestedScreen->delete();

        $this->assertEquals(0, Screen::where('title', 'screen')->count());
        $this->assertEquals(0, Script::where('title', 'script')->count());
        $this->assertEquals(0, ScreenCategory::where('name', 'category 2')->count());
        $this->assertEquals('category name old', $screenCategory1->refresh()->name);

        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $this->assertEquals(1, Screen::where('title', 'screen')->count());
        $this->assertEquals(1, ScreenCategory::where('name', 'category 2')->count());
        $this->assertEquals('category 1', $screenCategory1->refresh()->name);

        $checkScreen = Screen::where('title', 'screen')->first();
        $checkScript = Script::where('title', 'script')->first();
        $checkNestedScreen = Screen::where('title', 'nested screen')->first();
        $checkNestedNestedScreen = Screen::where('title', 'nested nested screen')->first();
        $this->assertEquals($checkNestedScreen->id, Arr::get($checkScreen->config, '0.items.2.config.screen'));
        $this->assertEquals($checkNestedNestedScreen->id, Arr::get($checkNestedScreen->config, '0.items.2.config.screen'));
        $this->assertEquals($checkScript->id, Arr::get($checkScreen->watchers, '0.script_id'));
        $this->assertEquals('script-' . $checkScript->id, Arr::get($checkScreen->watchers, '0.script.id'));
    }

    private function importWithCopy($screenMode)
    {
        list($screen, $screenCategory1, $screenCategory2) =
            $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $optionsArray = [];
        $optionsArray[$screen->uuid] = ['mode' => $screenMode];
        $optionsArray[$screenCategory1->uuid] = ['mode' => 'copy']; // Ignored. Uses the screen's mode
        $optionsArray[$screenCategory2->uuid] = ['mode' => 'copy']; // Ignored. Uses the screen's mode

        $options = new Options($optionsArray);
        $importer = new Importer($payload, $options);

        $importer->doImport();

        return $screen;
    }

    public function testImportNewCategoryWithExistingScreen()
    {
        $screen = $this->importWithCopy('update');
        $screen->refresh();
        $categories = $screen->refresh()->categories()->pluck('name', 'screen_categories.id as id');

        // Not touched. Categories use the screen's mode (update)
        $this->assertCount(2, $categories);
        $this->assertContains('category 1', $categories);
        $this->assertContains('category 2', $categories);
    }

    public function testImportNewCategoryWithNewScreen()
    {
        $this->importWithCopy('copy');
        $screen = Screen::where('title', 'screen 2')->firstOrFail();
        $categories = $screen->refresh()->categories()->pluck('name', 'screen_categories.id as id');
        $this->assertCount(2, $categories);
        $this->assertContains('category 3', $categories);
        $this->assertContains('category 4', $categories);
    }

    public function testSeededScreensWithKeyAttribute()
    {
        \DB::beginTransaction();
        $exportedScreen = Screen::factory()->create(['title' => 'exported screen', 'key' => 'foo']);
        $exportedScreenUuid = $exportedScreen->uuid;
        $payload = $this->export($exportedScreen, ScreenExporter::class);
        \DB::rollBack();

        $existingScreen = Screen::factory()->create(['title' => 'existing screen', 'key' => 'foo']);
        $this->import($payload);
        $existingScreen->refresh();

        // If a key attribute exists, use the key to find the model, not the UUID
        $this->assertEquals($exportedScreenUuid, $existingScreen->uuid);
        $this->assertEquals('exported screen', $existingScreen->title);
    }

    public function testScreenWithScriptWatcher()
    {
        $screen = Screen::factory()->create(['title' => 'Screen with script watcher', 'key' => 'foo']);
        $script = Script::factory()->create(['title' => 'script']);
        $this->associateScriptWatcher($screen, $script);

        $this->runExportAndImport($screen, ScreenExporter::class, function () use ($script) {
            Screen::query()->forceDelete();
            Script::query()->forceDelete();
            $this->assertEquals(0, Screen::get()->count());
            $this->assertEquals(0, Script::get()->count());
        });

        $importedScreen = Screen::where('title', 'Screen with script watcher')->firstOrFail();
        $importedScript = Script::where('id', Arr::get($importedScreen, 'watchers.0.script_id'))->firstOrFail();

        $this->assertIsString(Arr::get($importedScreen, 'watchers.0.script_id'));
        $this->assertEquals(Arr::get($importedScreen, 'watchers.0.script.title'), $importedScript->title);
        $this->assertEquals(Arr::get($importedScreen, 'watchers.0.script_id'), $importedScript->id);
    }

    private function associateNestedScreen($parent, $child)
    {
        $config = $parent->config;
        Arr::set($config, '0.items.2.config.screen', $child->id);
        $parent->config = $config;
        $parent->saveOrFail();
    }

    private function associateScriptWatcher($screen, $script)
    {
        $watchers = $screen->watchers;
        Arr::set($watchers, '0.script.id', 'script-' . $script->id);
        Arr::set($watchers, '0.script.uuid', $script->uuid);
        Arr::set($watchers, '0.script_id', $script->id);
        $screen->watchers = $watchers;
        $screen->saveOrFail();
    }

    public function testExportScreenInLoop()
    {
        $child1 = Screen::factory()->create(['title' => 'first child screen']);
        $child2 = Screen::factory()->create(['title' => 'second child screen']);
        $child3 = Screen::factory()->create(['title' => 'third child screen']);
        $config = file_get_contents(__DIR__ . '/../fixtures/screen_with_nested_screens_in_loop_and_multicolumns.json');
        $config = json_decode($config, true);
        Arr::set($config, '0.items.1.items.0.items.0.0.config.screen', $child1->id);
        Arr::set($config, '0.items.1.items.0.items.1.0.config.screen', $child2->id);
        Arr::set($config, '0.items.1.items.1.config.screen', $child3->id);
        $parent = Screen::factory()->create(['title' => 'parent screen', 'config' => $config]);

        $exporter = new Exporter();
        $exporter->exportScreen($parent);
        $payload = $exporter->payload();

        // Assert the child was exported in the payload
        $this->assertEquals('first child screen', Arr::get($payload, "export.{$child1->uuid}.attributes.title"));
        $this->assertEquals('second child screen', Arr::get($payload, "export.{$child2->uuid}.attributes.title"));
        $this->assertEquals('third child screen', Arr::get($payload, "export.{$child3->uuid}.attributes.title"));

        $options = new Options([
            $parent->uuid => ['mode' => 'copy'],
            $child1->uuid => ['mode' => 'copy'],
            $child2->uuid => ['mode' => 'copy'],
            $child3->uuid => ['mode' => 'copy'],
        ]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $newParent = Screen::where('title', 'parent screen 2')->firstOrFail();
        $newChild1 = Screen::where('title', 'first child screen 2')->firstOrFail();
        $newChild2 = Screen::where('title', 'second child screen 2')->firstOrFail();
        $newChild3 = Screen::where('title', 'third child screen 2')->firstOrFail();

        // Assert the new child was correctly associated in the new parent config
        $this->assertEquals($newChild1->id, Arr::get($newParent->config, '0.items.1.items.0.items.0.0.config.screen'));
        $this->assertEquals($newChild2->id, Arr::get($newParent->config, '0.items.1.items.0.items.1.0.config.screen'));
        $this->assertEquals($newChild3->id, Arr::get($newParent->config, '0.items.1.items.1.config.screen'));
    }
}
