<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\SignalEventDefinition;
use ProcessMaker\Models\User;
use SignalSeeder;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ProcessExporterTest extends TestCase
{
    use HelperTrait;

    private function fixtures()
    {
        // Create simple screens. Extensive screen tests are in ScreenExporterTest.php
        $screen = factory(Screen::class)->create(['title' => 'Screen']);
        $cancelScreen = factory(Screen::class)->create();
        $requestDetailScreen = factory(Screen::class)->create();
        $user = factory(User::class)->create(['username' => 'testuser']);

        $process = $this->createProcess('basic-process', [
            'name' => 'Process',
            'user_id' => $user->id,
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
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

        return [$process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2];
    }

    public function testExport()
    {
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $tree = $exporter->tree();

        $this->assertEquals($process->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($process->category->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($screen->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($cancelScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
        $this->assertEquals($requestDetailScreen->uuid, Arr::get($tree, '0.dependents.4.uuid'));
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
        $process = $this->createProcess('process-with-signals.bpmn.xml', [
            'name' => 'my process',
        ]);

        $this->runExportAndImport('exportProcess', $process, function () use ($signal, $process) {
            SignalManager::removeSignal($signal);
            $this->assertNull(SignalManager::findSignal('test_global_signal'));
            $process->forceDelete();
        });

        $this->assertNotNull(SignalManager::findSignal('test_global_signal'));

        $globalSignals = SignalManager::getAllSignals(true, [SignalManager::getGlobalSignalProcess()])->toArray();
        $this->assertEquals('test_global_signal', $globalSignals[0]['id']);
    }

    public function testSubprocesses()
    {
        $parentProcess = $this->createProcess('process-with-different-kinds-of-call-activities', ['name' => 'parent']);
        $subProcess = $this->createProcess('basic-process', ['name' => 'sub']);
        $packageProcess = $this->createProcess('basic-process', ['name' => 'package', 'package_key' => 'foo']);

        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'calledElement', 'ProcessId-' . $packageProcess->id);
        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'calledElement', 'ProcessId-' . $subProcess->id);
        $parentProcess->save();

        $this->runExportAndImport('exportProcess', $parentProcess, function () use ($parentProcess, $subProcess, $packageProcess) {
            $subProcess->forceDelete();
            $parentProcess->forceDelete();
            $packageProcess->forceDelete();
        });

        $parentProcess = Process::where('name', 'parent')->findOrFail();
        $subProcess = Process::where('name', 'sub')->findOrFail();
        $definitions = $parentProcess->getDefinitions(true);
        $element = Utils::getElementByPath($definitions, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]');
        $this->assertEquals('ProcessId-' . $subProcess->id, $element->getAttribute('calledElement'));
        $this->assertEquals($subProcess->id, Utils::getPmConfig($element)['calledElement']);
        $this->assertEquals(0, Process::where('name', 'package')->count());
    }
}
