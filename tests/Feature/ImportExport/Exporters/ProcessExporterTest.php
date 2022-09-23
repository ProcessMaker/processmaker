<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
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
        $group = factory(Group::class)->create(['name' => 'Administrators']);

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

        // Task Assignments.
        factory(ProcessTaskAssignment::class)->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_3',
            'assignment_id' => $user->id,
            'assignment_type' => User::class,
        ]);
        factory(ProcessTaskAssignment::class)->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_3',
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        return [$process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2, $subProcess, $group];
    }

    public function testExport()
    {
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2, $subProcess, $group) = $this->fixtures();

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
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2, $subprocess, $group) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        \DB::delete('delete from process_notification_settings');
        $process->forceDelete();
        $screen->delete();
        $user->delete();
        $group->delete();

        $this->assertEquals(0, Process::where('name', 'Process')->count());
        $this->assertEquals(0, Screen::where('title', 'Screen')->count());
        $this->assertEquals(0, User::where('username', 'testuser')->count());
        $this->assertEquals(0, Group::where('name', 'Administrators')->count());

        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $process = Process::where('name', 'Process')->firstOrFail();
        $user = $process->user;
        $this->assertEquals(1, Screen::where('title', 'Screen')->count());
        $this->assertEquals('testuser', $user->username);

        $notificationSettings = $process->notification_settings;
        $this->assertCount(2, $notificationSettings);
        $this->assertEquals('assigned', $notificationSettings[0]['notification_type']);
        $this->assertEquals('node_3', $notificationSettings[1]['element_id']);

        $taskAssignments = $process->assignments;
        $group = Group::firstWhere('name', 'Administrators');
        $this->assertCount(2, $taskAssignments);
        $this->assertEquals($user->id, $taskAssignments[0]['assignment_id']);
        $this->assertEquals($group->id, $taskAssignments[1]['assignment_id']);
    }
}
