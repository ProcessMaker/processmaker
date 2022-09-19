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
        $user = factory(User::class)->create();

        // Create Process.
        $bpmn = Process::getProcessTemplate('SingleTaskProcessManager.bpmn');
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $screen->id . '"', $bpmn);
        $process = factory(Process::class)->create([
            'name' => 'Process',
            'user_id' => $user->id,
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
            'bpmn' => $bpmn,
        ]);

        // TODO
        // Notification Settings.
        // factory(ProcessNotificationSetting::class)->create([
        //     'process_id' => $process->id,
        //     'notifiable_type' => 'requester',
        //     'notification_type' => 'assigned',
        // ]);

        return [$process, $screen, $cancelScreen, $requestDetailScreen, $user];
    }

    public function testExport()
    {
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $tree = $exporter->tree();

        $this->assertEquals($process->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($process->category->uuid, Arr::get($tree, '0.dependents.0.uuid'));
        $this->assertEquals($screen->uuid, Arr::get($tree, '0.dependents.1.uuid'));
        $this->assertEquals($cancelScreen->uuid, Arr::get($tree, '0.dependents.2.uuid'));
        $this->assertEquals($requestDetailScreen->uuid, Arr::get($tree, '0.dependents.3.uuid'));
    }

    public function testImport()
    {
        list($process, $screen, $cancelScreen, $requestDetailScreen, $user) = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $process->delete();
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
        $this->assertEquals('testuser', $process->user);
    }
}
