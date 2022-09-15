<?php

namespace Tests;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;

class ExporterTest extends TestCase
{
    public function testExportScreen()
    {
        $screen = factory(Screen::class)->create();
        $screenCategory1 = factory(ScreenCategory::class)->create();
        $screenCategory2 = factory(ScreenCategory::class)->create();
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;

        $script = factory(Script::class)->create();
        $watcher = ['name' => 'Watcher', 'script_id' => $script->id];
        $screen->watchers = [$watcher];

        $nestedScreen = factory(Screen::class)->create();
        $nestedScreen->screen_category_id = $screenCategory1->id;
        $item = [
            'label' => 'Nested Screen',
            'component' => 'FormNestedScreen',
            'config' => [
                'value' => null,
                'screen' => $nestedScreen->id,
            ],
        ];
        $screen->config = ['items' => [$item]];

        $screen->save();
        $exporter = new Exporter();
        $tree = $exporter->exportScreen($screen);

        $this->assertEquals($screen->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($screenCategory1->uuid, Arr::get($tree, '0.dependents.0.uuid'));
        $this->assertEquals($screenCategory2->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($script->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($nestedScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
        $this->assertEquals($screenCategory1->uuid, Arr::get($tree, '0.dependents.3.dependents.0.uuid'));
    }
}
