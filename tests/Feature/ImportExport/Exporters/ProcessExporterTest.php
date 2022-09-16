<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;
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
        $environmentVariable = factory(EnvironmentVariable::class)->create(['name' => 'TEST_VAR']);
        $script = factory(Script::class)->create([
            'code' => '<?php $config["envVar"] = getenv("TEST_VAR"); return $config; ?>',
        ]);
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

        // Notification Settings.
        factory(ProcessNotificationSetting::class)->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
        ]);

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $tree = $exporter->tree();

        $this->assertEquals($process->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($process->category->uuid, Arr::get($tree, '0.dependents.0.uuid'));
        $this->assertEquals($screen->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($script->uuid, Arr::get($tree, '0.dependents.1.dependents.2.uuid'));
        $this->assertEquals($nestedScreen->uuid, Arr::get($tree, '0.dependents.1.dependents.3.uuid'));
        $this->assertEquals($environmentVariable->uuid, Arr::get($tree, '0.dependents.1.dependents.2.dependents.1.uuid'));
        $this->assertEquals($script->scriptExecutor->uuid, Arr::get($tree, '0.dependents.1.dependents.2.dependents.2.uuid'));
        $this->assertEquals($cancelScreen->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($requestDetailScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
    }

    public function testImport()
    {
    }
}
