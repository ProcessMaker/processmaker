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
        $screen = $this->createScreen();
        $screenCategory1 = factory(ScreenCategory::class)->create(['name' => 'category 1']);
        $screenCategory2 = factory(ScreenCategory::class)->create(['name' => 'category 2']);
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;

        $script = factory(Script::class)->create(['title' => 'script']);
        $this->associateScriptWatcher($screen, $script);

        $nestedScreen = $this->createScreen('nested screen', false);
        $nestedScreen->screen_category_id = $screenCategory1->id;
        $nestedNestedScreen = factory(Screen::class)->create(['title' => 'nested nested screen']);
        $nestedNestedScreen->screen_category_id = $screenCategory2->id;
        $this->associateNestedScreen($nestedScreen, $nestedNestedScreen);
        $this->associateNestedScreen($screen, $nestedScreen);

        $screen->save();
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
        $screen = factory(Screen::class)->create(['title' => 'screen 1']);
        $screenCategory1 = factory(ScreenCategory::class)->create(['name' => 'category 1']);
        $screenCategory2 = factory(ScreenCategory::class)->create(['name' => 'category 2']);
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;
        $screen->save();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $screen->delete();
        $screenCategory1->update(['name' => 'category name old']);
        $screenCategory2->delete();

        $this->assertEquals(0, Screen::where('title', 'screen 1')->count());
        $this->assertEquals(0, ScreenCategory::where('name', 'category 2')->count());
        $this->assertEquals('category name old', $screenCategory1->refresh()->name);

        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $this->assertEquals(1, Screen::where('title', 'screen 1')->count());
        $this->assertEquals(1, ScreenCategory::where('name', 'category 2')->count());
        $this->assertEquals('category 1', $screenCategory1->refresh()->name);
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
        $screen = factory(Screen::class)->create([
            'title' => $title,
            'config' => $config,
            'watchers' => $watchers,
        ]);

        return $screen;
    }
}
