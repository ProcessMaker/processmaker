<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use Tests\TestCase;

class ScreenExporterTest extends TestCase
{
    public function testExport()
    {
        list($screen, $screenCategory1, $screenCategory2, $script, $nestedScreen, $nestedNestedScreen) =
            $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $tree = $exporter->tree();

        $this->assertCount(4, Arr::get($tree, '0.dependents'));
        $this->assertEquals($screen->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($screenCategory1->uuid, Arr::get($tree, '0.dependents.0.uuid'));
        $this->assertEquals($screenCategory2->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($script->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($script->category->uuid, Arr::get($tree, '0.dependents.2.dependents.0.uuid'));
        $this->assertEquals($nestedScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
        $this->assertEquals($nestedNestedScreen->uuid, Arr::get($tree, '0.dependents.3.dependents.1.uuid'));
        $this->assertEquals($screenCategory1->uuid, Arr::get($tree, '0.dependents.3.dependents.0.uuid'));
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

    private function importWithNew($screenMode)
    {
        list($screen, $screenCategory1, $screenCategory2, $script, $nestedScreen, $nestedNestedScreen) =
            $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $optionsArray = [];
        $optionsArray[$screen->uuid] = ['mode' => $screenMode];
        $optionsArray[$screenCategory1->uuid] = ['mode' => 'new'];
        $optionsArray[$screenCategory2->uuid] = ['mode' => 'new'];

        $options = new Options($optionsArray);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        return $screen;
    }

    public function testImportNewCategoryWithExistingScreen()
    {
        $screen = $this->importWithNew('update');
        $categories = $screen->refresh()->categories()->pluck('name', 'screen_categories.id as id');
        $this->assertCount(4, $categories);
        $this->assertContains('category 1', $categories);
        $this->assertContains('category 2', $categories);
        $this->assertContains('category 3', $categories);
        $this->assertContains('category 4', $categories);
    }

    public function testImportNewCategoryWithNewScreen()
    {
        $this->importWithNew('new');
        $screen = Screen::where('title', 'screen 2')->firstOrFail();
        $categories = $screen->refresh()->categories()->pluck('name', 'screen_categories.id as id');
        $this->assertCount(2, $categories);
        $this->assertContains('category 3', $categories);
        $this->assertContains('category 4', $categories);
    }

    private function fixtures()
    {
        $screen = $this->createScreen();
        $screenCategory1 = ScreenCategory::factory()->create(['name' => 'category 1', 'status' => 'ACTIVE']);
        $screenCategory2 = ScreenCategory::factory()->create(['name' => 'category 2', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;

        $script = Script::factory()->create(['title' => 'script']);
        $this->associateScriptWatcher($screen, $script);

        $nestedScreen = $this->createScreen('nested screen', false);
        $nestedScreen->screen_category_id = $screenCategory1->id;
        $nestedNestedScreen = Screen::factory()->create(['title' => 'nested nested screen', 'config' => []]);
        $nestedNestedScreen->screen_category_id = $screenCategory2->id;
        $this->associateNestedScreen($nestedScreen, $nestedNestedScreen);
        $this->associateNestedScreen($screen, $nestedScreen);

        $screen->save();

        return [$screen, $screenCategory1, $screenCategory2, $script, $nestedScreen, $nestedNestedScreen];
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

    private function createScreen($title = 'screen', $addWatchers = true)
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../fixtures/screen_with_nested_screen.json'), true);
        $watchers = $addWatchers ? json_decode(file_get_contents(__DIR__ . '/../fixtures/watchers.json'), true) : [];
        $screen = Screen::factory()->create([
            'title' => $title,
            'config' => $config,
            'watchers' => $watchers,
        ]);

        return $screen;
    }
}
