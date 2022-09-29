<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\SignalEventDefinition;
use ProcessMaker\Models\User;
use SignalSeeder;
use Tests\TestCase;

class ProcessExporterTest extends TestCase
{
    private function fixtures()
    {
        // Create simple screens. Extensive screen tests are in ScreenExporterTest.php
        $screen = factory(Screen::class)->create(['title' => 'Screen']);
        $cancelScreen = factory(Screen::class)->create();
        $requestDetailScreen = factory(Screen::class)->create();
        $user = factory(User::class)->create(['username' => 'testuser']);

        // Create SubProcess.
        $subProcessBpmn = Process::getProcessTemplate('ScriptTask.bpmn');
        $subProcess = factory(Process::class)->create([
            'name' => 'SubProcess',
            'user_id' => $user->id,
            'bpmn' => $subProcessBpmn,
        ]);

        // Create Process with SubProcess.
        $bpmn = Process::getProcessTemplate('ExportProcess.bpmn');
        $bpmn = str_replace(
            ['pm:screenRef="1"', 'calledElement="ProcessId-1"'],
            ['pm:screenRef="' . $screen->id . '"', 'calledElement="ProcessId-' . $subProcess->id . '"'],
            $bpmn
        );

        $process = factory(Process::class)->create([
            'name' => 'Process',
            'user_id' => $user->id,
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
            'bpmn' => $bpmn,
        ]);

        // Notification Settings.
        $processNotificationSetting1 = factory(ProcessNotificationSetting::class)->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
        ]);
        $processNotificationSetting2 = factory(ProcessNotificationSetting::class)->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
            'element_id' => 'node_3',
        ]);

        return [$process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2, $subProcess];
    }

    public function testExport()
    {
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2, $subProcess) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $tree = $exporter->tree();

        $this->assertEquals($process->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($process->category->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($screen->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($cancelScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
        $this->assertEquals($requestDetailScreen->uuid, Arr::get($tree, '0.dependents.4.uuid'));
        $this->assertEquals($subProcess->uuid, Arr::get($tree, '0.dependents.5.uuid'));
    }

    public function testImport()
    {
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        \DB::delete('delete from process_notification_settings');
        $process->forceDelete();
        $screen->delete();
        $user->delete();

        $this->assertEquals(0, Process::where('name', 'Process')->count());
        $this->assertEquals(0, Screen::where('title', 'Screen')->count());
        $this->assertEquals(0, User::where('username', 'testuser')->count());

        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $process = Process::where('name', 'Process')->firstOrFail();
        $this->assertEquals(1, Screen::where('title', 'Screen')->count());
        $this->assertEquals('testuser', $process->user->username);

        $notificationSettings = $process->notification_settings;
        $this->assertCount(2, $notificationSettings);
        $this->assertEquals('assigned', $notificationSettings[0]['notification_type']);
        $this->assertEquals('node_3', $notificationSettings[1]['element_id']);
    }

    public function testSignals()
    {
        factory(ProcessCategory::class)->create(['is_system'=> true]);
        (new SignalSeeder())->run();
        $signal = new SignalData('test_global_signal', 'test global signal', '');
        SignalManager::addSignal($signal);

        $process = factory(Process::class)->create([
            'bpmn' => file_get_contents(__DIR__ . '/../fixtures/process-with-signals.bpmn.xml'),
            'name' => 'my process',
        ]);

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        SignalManager::removeSignal($signal);
        $this->assertNull(SignalManager::findSignal('test_global_signal'));
        $process->forceDelete();

        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $this->assertNotNull(SignalManager::findSignal('test_global_signal'));

        $globalSignals = SignalManager::getAllSignals(true, [SignalManager::getGlobalSignalProcess()])->toArray();
        $this->assertEquals('test_global_signal', $globalSignals[0]['id']);
    }
}
