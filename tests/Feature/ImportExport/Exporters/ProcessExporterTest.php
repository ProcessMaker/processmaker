<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use Tests\TestCase;

class ProcessExporterTest extends TestCase
{
    public function testExport()
    {
        // Create Screens.
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
        $cancelScreen = factory(Screen::class)->create();
        $requestDetailScreen = factory(Screen::class)->create();

        // Create Process.
        $bpmn = Process::getProcessTemplate('SingleTaskProcessManager.bpmn');
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $screen->id . '"', $bpmn);
        $process = factory(Process::class)->create([
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
            'bpmn' => $bpmn,
        ]);

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $tree = $exporter->tree();

        dd($tree);

        $this->assertEquals($screen->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($screenCategory1->uuid, Arr::get($tree, '0.dependents.0.uuid'));
        $this->assertEquals($screenCategory2->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($script->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($script->category->uuid, Arr::get($tree, '0.dependents.2.dependents.0.uuid'));
        $this->assertEquals($nestedScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
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
        $screenCategory2->delete();

        $this->assertEquals(0, Screen::where('title', 'screen 1')->count());
        $this->assertEquals(0, ScreenCategory::where('name', 'category 2')->count());

        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $this->assertEquals(1, Screen::where('title', 'screen 1')->count());
        $this->assertEquals(1, ScreenCategory::where('name', 'category 2')->count());
    }
}
