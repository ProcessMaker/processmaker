<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\ImportExport\Tree;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\SignalEventDefinition;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ProcessExporterTest extends TestCase
{
    use HelperTrait;

    private function fixtures()
    {
        // Create simple screens. Extensive screen tests are in ScreenExporterTest.php
        $cancelScreen = $this->createScreen('basic-form-screen', ['title' => 'Cancel Screen']);
        $requestDetailScreen = $this->createScreen('basic-display-screen', ['title' => 'Request Detail Screen']);

        $manager = User::factory()->create(['username' => 'manager']);
        $group = Group::factory()->create(['name' => 'Group', 'description' => 'My Example Group', 'manager_id' => $manager->id]);
        $user = User::factory()->create(['username' => 'testuser']);
        $user->groups()->sync([$group->id]);

        $process = $this->createProcess('basic-process', [
            'name' => 'Process',
            'user_id' => $user->id,
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
        ]);

        // Notification Settings.
        $processNotificationSetting1 = ProcessNotificationSetting::factory()->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
        ]);
        $processNotificationSetting2 = ProcessNotificationSetting::factory()->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
            'element_id' => 'node_3',
        ]);

        return [$process, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2];
    }

    public function testExport()
    {
        $this->addGlobalSignalProcess();

        list($process, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $tree = $exporter->tree();

        $this->assertEquals($process->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($process->category->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($cancelScreen->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($requestDetailScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
        $this->assertEquals($user->groups->first()->uuid, Arr::get($tree, '0.dependents.0.dependents.0.uuid'));
    }

    public function testImport()
    {
        list($process, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2) = $this->fixtures();

        $this->runExportAndImport($process, ProcessExporter::class, function () use ($process, $cancelScreen, $requestDetailScreen, $user) {
            \DB::delete('delete from process_notification_settings');
            $process->forceDelete();
            $cancelScreen->delete();
            $requestDetailScreen->delete();
            $user->groups->first()->manager->delete();
            $user->groups()->delete();
            $user->delete();

            $this->assertEquals(0, Process::where('name', 'Process')->count());
            $this->assertEquals(0, Screen::where('title', 'Request Detail Screen')->count());
            $this->assertEquals(0, Screen::where('title', 'Cancel Screen')->count());
            $this->assertEquals(0, User::where('username', 'testuser')->count());
            $this->assertEquals(0, Group::where('name', 'Group')->count());
        });

        $process = Process::where('name', 'Process')->firstOrFail();
        $this->assertEquals(1, Screen::where('title', 'Request Detail Screen')->count());
        $this->assertEquals(1, Screen::where('title', 'Cancel Screen')->count());
        $this->assertEquals('testuser', $process->user->username);

        $group = $process->user->groups->first();
        $this->assertEquals('Group', $group->name);
        $this->assertEquals('My Example Group', $group->description);
        $this->assertEquals($user->groups->first()->manager->id, $group->manager_id);

        $notificationSettings = $process->notification_settings;
        $this->assertCount(2, $notificationSettings);
        $this->assertEquals('assigned', $notificationSettings[0]['notification_type']);
        $this->assertEquals('node_3', $notificationSettings[1]['element_id']);
    }

    public function testSignals()
    {
        $processA = $this->createProcess('signal-process-a', [
            'name' => 'signal process a',
        ]);

        $processB = $this->createProcess('signal-process-b', [
            'name' => 'signal process b',
        ]);

        $this->runExportAndImport($processA, ProcessExporter::class, function () use ($processA, $processB) {
            SignalManager::getGlobalSignalProcess()->forceDelete();
            $processA->forceDelete();
            $processB->forceDelete();
            app()->forgetInstance(SignalHelper::class);
            $this->addGlobalSignalProcess();
        });

        $globalSignals = app()->make(SignalHelper::class)->getGlobalSignals()->toArray();
        $this->assertContains('test_global', $globalSignals);

        $this->assertEquals(1, Process::where('name', 'signal process b')->count());
    }

    public function testSubprocesses()
    {
        $parentProcess = $this->createProcess('process-with-different-kinds-of-call-activities', ['name' => 'parent']);
        $subProcess = $this->createProcess('basic-process', ['name' => 'sub']);
        $packageProcess = $this->createProcess('basic-process', ['name' => 'package', 'package_key' => 'foo']);

        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'calledElement', 'ProcessId-' . $packageProcess->id);
        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'calledElement', 'ProcessId-' . $subProcess->id);
        $parentProcess->save();

        $this->runExportAndImport($parentProcess, ProcessExporter::class, function () use ($parentProcess, $subProcess, $packageProcess) {
            $subProcess->forceDelete();
            $parentProcess->forceDelete();
            $packageProcess->forceDelete();
        });

        $parentProcess = Process::where('name', 'parent')->firstOrFail();
        $subProcess = Process::where('name', 'sub')->firstOrFail();
        $definitions = $parentProcess->getDefinitions(true);
        $element = Utils::getElementByPath($definitions, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]');

        $this->assertEquals('ProcessId-' . $subProcess->id, $element->getAttribute('calledElement'));
        $this->assertEquals('ProcessId-' . $subProcess->id, Utils::getPmConfig($element)['calledElement']);
        $this->assertEquals($subProcess->id, Utils::getPmConfig($element)['processId']);
        $this->assertEquals(0, Process::where('name', 'package')->count());
    }
}
