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
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
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
        $payload = $exporter->payload();

        $processDependents = Arr::get($payload, 'export.' . $process->uuid . '.dependents');
        $processDependentUuids = Arr::pluck($processDependents, 'uuid');

        $this->assertContains($process->category->uuid, $processDependentUuids);
        $this->assertContains($cancelScreen->uuid, $processDependentUuids);
        $this->assertContains($requestDetailScreen->uuid, $processDependentUuids);
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

        // No longer exporting users
        $this->assertNull($process->user);
        $this->assertDatabaseMissing('groups', ['name' => 'Group']);

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

        $this->runExportAndImport($processA, ProcessExporter::class, function () use ($processA) {
            SignalManager::getGlobalSignalProcess()->forceDelete();
            $processA->forceDelete();
            app()->forgetInstance(SignalHelper::class);
            $this->addGlobalSignalProcess();
        });

        $globalSignals = app()->make(SignalHelper::class)->getGlobalSignals()->toArray();
        $this->assertContains('test_global', $globalSignals);
    }

    public function testSubprocesses()
    {
        $screen = Screen::factory()->create(['title' => 'Screen A']);
        $parentProcess = $this->createProcess('process-with-different-kinds-of-call-activities', ['name' => 'parent']);
        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screen->id);
        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:task[2]', 'pm:screenRef', $screen->id);
        $subProcess = $this->createProcess('basic-process', ['name' => 'sub']);
        $packageProcess = $this->createProcess('basic-process', ['name' => 'package', 'package_key' => 'foo']);

        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'calledElement', 'ProcessId-' . $packageProcess->id);
        Utils::setAttributeAtXPath($parentProcess, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'calledElement', 'ProcessId-' . $subProcess->id);
        $parentProcess->save();

        $this->runExportAndImport($parentProcess, ProcessExporter::class, function () use ($parentProcess, $subProcess, $packageProcess, $screen) {
            $screen->forceDelete();
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

    public function testProcessTaskScreen()
    {
        // Create process from template
        $process = $this->createProcess('process-with-task-screen', ['name' => 'Process with task']);
        // Create screens
        $screenA = Screen::factory()->create(['title' => 'Screen A', 'type' => 'FORM']);
        $screenB = Screen::factory()->create(['title' => 'Screen B', 'type' => 'FORM']);
        $interstitialScreen = Screen::factory()->create(['title' => 'Interstitial Screen', 'type' => 'DISPLAY']);

        // Set id's of screens
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screenA->id);
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:interstitialScreenRef', $interstitialScreen->id);
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[2]', 'pm:screenRef', $screenB->id);
        $process->save();

        // Export and Import process
        $this->runExportAndImport($process, ProcessExporter::class, function () use ($process, $screenA, $screenB, $interstitialScreen) {
            $process->forceDelete();
            $screenA->forceDelete();
            $screenB->forceDelete();
            $interstitialScreen->forceDelete();
            $this->assertDatabaseMissing('processes', ['name' => $process->name]);
            $this->assertDatabaseMissing('screens', ['title' => $screenA->title]);
            $this->assertDatabaseMissing('screens', ['title' => $screenB->title]);
            $this->assertDatabaseMissing('screens', ['title' => $interstitialScreen->title]);
        });

        // Assert that the process and screen exist in the database
        $this->assertDatabaseHas('processes', ['name' => $process->name]);
        $this->assertDatabaseHas('screens', ['title' => $screenA->title]);
        $this->assertDatabaseHas('screens', ['title' => $screenB->title]);
        $this->assertDatabaseHas('screens', ['title' => $interstitialScreen->title]);

        // Get imported process
        $process = Process::where('name', 'Process with task')->firstOrFail();

        // Get task config
        $tasks = [
            '/bpmn:definitions/bpmn:process/bpmn:task[1]' => ['title' => $screenA->title, 'interstitialTitle' => $interstitialScreen->title],
            '/bpmn:definitions/bpmn:process/bpmn:task[2]' => ['title' => $screenB->title],
        ];

        // Assert that screens have been imported properly
        $definitions = $process->getDefinitions(true);
        foreach ($tasks as $path => $taskScreen) {
            $element = Utils::getElementByPath($definitions, $path);

            $screenRef = $element->getAttribute('pm:screenRef');
            $interstitialScreenRef = $element->getAttribute('pm:interstitialScreenRef');
            $importedScreen = Screen::where('id', $screenRef)->firstOrFail();

            $this->assertEquals($importedScreen->title, $taskScreen['title']);

            if (is_numeric($interstitialScreenRef)) {
                $importedInterstitialScreen = Screen::where('id', $interstitialScreenRef)->firstOrFail();
                $this->assertEquals($importedInterstitialScreen->title, $taskScreen['interstitialTitle']);
            }
        }
    }

    public function testProcessTaskScript()
    {
        // Create script
        $category = ScriptCategory::factory()->create(['name' => 'test category']);
        $scriptUser = User::factory()->create(['username' => 'scriptuser']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php return [];',
            'run_as_user_id' => $scriptUser->id,
        ]);
        $script->categories()->sync($category);

        // Create process from template
        $process = $this->createProcess('process-with-task-script', ['name' => 'Process with script task']);

        // Set script on process
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:scriptTask', 'pm:scriptRef', $script->id);
        $process->save();

        // Export and Import process
        $this->runExportAndImport($process, ProcessExporter::class, function () use ($process, $script) {
            $process->forceDelete();
            $script->forceDelete();
            $this->assertDatabaseMissing('processes', ['name' => $process->name]);
            $this->assertDatabaseMissing('scripts', ['title' => $script->title]);
        });

        // Assert that the process and screen exist in the database
        $this->assertDatabaseHas('processes', ['name' => $process->name]);
        $this->assertDatabaseHas('scripts', ['title' => $script->title]);

        // Get imported process
        $importedProcess = Process::where('name', 'Process with script task')->firstOrFail();

        // Get task config
        $scriptTasks = [
            '/bpmn:definitions/bpmn:process/bpmn:scriptTask' => ['title' => $script->title],
        ];

        // Assert that scripts have been imported properly
        $definitions = $importedProcess->getDefinitions(true);

        foreach ($scriptTasks as $path => $scriptTask) {
            $element = Utils::getElementByPath($definitions, $path);

            $scriptRef = $element->getAttribute('pm:scriptRef');
            $importedScript = Script::where('id', $scriptRef)->firstOrFail();

            $this->assertEquals($importedScript->title, $scriptTask['title']);
        }
    }
}
