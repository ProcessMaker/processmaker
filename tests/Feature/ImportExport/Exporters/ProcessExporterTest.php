<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ProcessExporterTest extends TestCase
{
    use HelperTrait;

    /**
     *  Init admin user
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminUser();
    }

    private function fixtures(): array
    {
        // Create simple screens. Extensive screen tests are in ScreenExporterTest.php
        $cancelScreen = $this->createScreen('basic-form-screen', ['title' => 'Cancel Screen']);
        $requestDetailScreen = $this->createScreen('basic-display-screen', ['title' => 'Request Detail Screen']);

        $manager = User::factory()->create(['username' => 'manager']);
        $group = Group::factory()->create([
            'name' => 'Group',
            'description' => 'My Example Group',
            'manager_id' => $manager->id,
        ]);
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

        $media = $this->createFakeImage($process);

        return [
            'process' => $process,
            'cancelScreen' => $cancelScreen,
            'requestDetailScreen' => $requestDetailScreen,
            'user' => $user,
            'processNotificationSetting1' => $processNotificationSetting1,
            'processNotificationSetting2' => $processNotificationSetting2,
            'media' => $media,
        ];
    }

    public function testExport()
    {
        $this->addGlobalSignalProcess();
        [
            'process' => $process,
            'cancelScreen' => $cancelScreen,
            'requestDetailScreen' => $requestDetailScreen,
            'media' => $media,
        ] = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $processDependents = Arr::get($payload, 'export.' . $process->uuid . '.dependents');
        $processDependentUuids = Arr::pluck($processDependents, 'uuid');

        $this->assertContains($process->category->uuid, $processDependentUuids);
        $this->assertContains($cancelScreen->uuid, $processDependentUuids);
        $this->assertContains($requestDetailScreen->uuid, $processDependentUuids);
        $this->assertContains($media->uuid, $processDependentUuids);
    }

    public function testImport()
    {
        [
            'process' => $process,
            'cancelScreen' => $cancelScreen,
            'requestDetailScreen' => $requestDetailScreen,
            'user' => $user,
        ] = $this->fixtures();

        $this->runExportAndImport(
            $process,
            ProcessExporter::class,
            function () use ($process, $cancelScreen, $requestDetailScreen, $user) {
                DB::delete('delete from process_notification_settings');
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
                $this->assertEquals(0, Media::where('name', 'Image')->count());
            }
        );

        $process = Process::where('name', 'Process')->firstOrFail();
        $this->assertEquals(1, Screen::where('title', 'Request Detail Screen')->count());
        $this->assertEquals(1, Screen::where('title', 'Cancel Screen')->count());

        $group = $process->user->groups->first();
        $this->assertEquals('Group', $group->name);
        $this->assertEquals('My Example Group', $group->description);
        $this->assertEquals($user->groups->first()->manager->id, $group->manager_id);

        $notificationSettings = $process->notification_settings;
        $this->assertCount(2, $notificationSettings);
        $this->assertEquals('assigned', $notificationSettings[0]['notification_type']);
        $this->assertEquals('node_3', $notificationSettings[1]['element_id']);
        $this->assertEquals(1, $process->media()->count());
        $media = $process->media()->first();
        $fakeFilePath = $media->id . '/' . $media->file_name;
        $this->assertFileExists(Storage::disk('public')->path($fakeFilePath));
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

    public function testSubprocessInTargetInstance()
    {
        $this->addGlobalSignalProcess();

        DB::beginTransaction();
        $parentProcess = $this->createProcess('process-with-different-kinds-of-call-activities', ['name' => 'parent']);
        $subProcess = $this->createProcess('basic-process', ['name' => 'sub']);

        $xpath = '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]';
        Utils::setAttributeAtXPath($parentProcess, $xpath, 'calledElement', 'ProcessId-' . $subProcess->id);
        Utils::setPmConfigValueAtXPath($parentProcess, $xpath, 'calledElement', 'ProcessId-' . $subProcess->id);
        Utils::setPmConfigValueAtXPath($parentProcess, $xpath, 'processId', $subProcess->id);
        $parentProcess->save();

        $payload = $this->export($parentProcess, ProcessExporter::class, null, false);
        DB::rollBack(); // Delete all created items since DB::beginTransaction

        $this->import($payload);
        $process = Process::where('name', 'parent')->firstOrFail();
        $newSubProcess = Process::where('name', 'sub')->firstOrFail();

        $this->assertEquals(
            'ProcessId-' . $newSubProcess->id, Utils::getAttributeAtXPath($process, $xpath, 'calledElement')
        );
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

    public function testDiscardedAssetLinksOnImportIfItExistsOnTheTargetInstance()
    {
        $this->addGlobalSignalProcess();

        $subprocess = Process::factory()->create(['name' => 'subprocess']);
        $originalSubprocessUuid = $subprocess->uuid;
        $originalSubprocessId = $subprocess->id;

        $bpmn = file_get_contents(__DIR__ . '/../fixtures/process-with-subprocess.bpmn.xml');
        $bpmn = str_replace('[SUBPROCESS_ID]', $originalSubprocessId, $bpmn);

        $manager = User::factory()->create(['username' => 'orig-manager', 'email' => 'manager@test.com']);
        $process = Process::factory()->create(['manager_id' => $manager->id, 'bpmn' => $bpmn]);

        // Note: manager user and subprocess are not exported
        $payload = $this->export($process, ProcessExporter::class);

        $manager->forceDelete();
        $subprocess->forceDelete();

        // Create a different manager but with the same email as the original
        $differentManager = User::factory()->create(['username' => 'new-manager', 'email' => 'manager@test.com']);
        $subprocessWithSameUUID = Process::factory()->create(['uuid' => $originalSubprocessUuid, 'name' => 'different subprocess']);

        $process->manager_id = $differentManager->id;
        $process->save();

        $this->import($payload);

        $process->refresh();
        $this->assertEquals($differentManager->id, $process->manager_id);
        $this->assertNotEquals($originalSubprocessId, $subprocessWithSameUUID->id);

        $value = Utils::getAttributeAtXPath($process, '*/bpmn:callActivity', 'calledElement');
        $this->assertEquals('ProcessId-' . $subprocessWithSameUUID->id, $value);

        $pmconfig = json_decode(Utils::getAttributeAtXPath($process, '*/bpmn:callActivity', 'pm:config'), true);
        $this->assertEquals('ProcessId-' . $subprocessWithSameUUID->id, $pmconfig['calledElement']);
        $this->assertEquals($subprocessWithSameUUID->id, $pmconfig['processId']);
    }

    public function testDiscardedAssetDoesNotExistOnTargetInstance()
    {
        $this->addGlobalSignalProcess();

        $manager = User::factory()->create(['username' => 'manager']);
        $user = User::factory()->create(['username' => 'processuser']);
        $originalManagerId = $manager->id;
        $process = Process::factory()->create(['user_id' => $user->id, 'manager_id' => $manager->id, 'name' => 'exported name']);
        $originalProcessUuid = $process->uuid;

        $options = new Options([$manager->uuid => ['mode' => 'discard']]);
        $payload = $this->export($process, ProcessExporter::class, $options);

        $manager->forceDelete();
        $process->forceDelete();

        $differentManager = User::factory()->create();
        $processWithSameUUID = Process::factory()->create([
            'uuid' => $originalProcessUuid,
            'manager_id' => $differentManager->id,
            'name' => 'name on target instance',
        ]);

        $this->import($payload);
        $processWithSameUUID->refresh();

        $this->assertEquals('exported name', $processWithSameUUID->name);

        // Skip it from dependencies it if we can't find it
        $this->assertEquals($originalManagerId, $processWithSameUUID->manager_id);
    }

    public function testDiscardOnExport()
    {
        $this->addGlobalSignalProcess();
        [
            'user' => $user,
            'process' => $process,
        ] = $this->fixtures();

        $payload = $this->export($process, ProcessExporter::class, null, false);

        $manifest = $payload['export'];
        $this->assertArrayNotHasKey($user->uuid, $manifest);

        // Test with ignoreExplicitDiscard
        $payload = $this->export($process, ProcessExporter::class, null, true);

        $manifest = $payload['export'];
        $this->assertArrayHasKey($user->uuid, $manifest);
    }

    public function testImportMediaWithCopy()
    {
        $this->addGlobalSignalProcess();
        [
            'process' => $process,
            'media' => $media,
        ] = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $optionsArray[$process->uuid] = ['mode' => 'copy'];
        $optionsArray[$media->uuid] = ['mode' => 'copy'];
        $options = new Options($optionsArray);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $this->assertEquals(1, $process->media()->count());
        $newProcess = Process::where('name', 'Process 2')->first();
        $this->assertEquals(1, $newProcess->media()->count());
        $this->assertEquals(2, Media::count());
    }

    private function createFakeImage(Process $process): Media
    {
        $media = Media::factory()->create([
            'uuid' => '8ef7550c-2544-4642-91e1-732fb267179a',
            'model_type' => Process::class,
            'model_id' => $process->id,
            'collection_name' => 'images_carousel',
            'name' => 'image',
            'file_name' => 'image.png',
        ]);

        // Create a fake image and save it directly to the fake storage.
        Storage::fake('public');
        $fileName = $media->name . '.png';
        $fileUpload = UploadedFile::fake()->image($fileName);
        $fakeFilePath = $media->id . '/' . $fileName;
        Storage::disk('public')->put($fakeFilePath, $fileUpload->getContent());

        return $media;
    }
}
