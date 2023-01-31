<?php

namespace Tests\Feature\ImportExport;

use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\User;
use ProcessMaker\Package\Versions\Models\VersionHistory;
use Tests\TestCase;

class ManifestTest extends TestCase
{
    use HelperTrait;

    private function mockExporter($dependents)
    {
        return $this->mock(ScreenExporter::class, function ($mock) use ($dependents) {
            $dependentMocks = [];
            foreach ($dependents as $dependent) {
                $dependentMocks[] = $this->mock(Dependent::class, function ($dependentMock) use ($dependent) {
                    $dependentMock->uuid = $dependent;
                });
            }
            $mock->dependents = $dependentMocks;
        });
    }

    public function testDiscardAndUpdateImportOption()
    {
        list($payload, $screen, $screenCategory) = $this->createScreen();

        $options = new Options([
            $screen->uuid => ['mode' => 'update'],
            $screenCategory->uuid => ['mode' => 'discard'],
        ]);
        (new Importer($payload, $options))->doImport();

        $this->assertEquals('exported screen', $screen->refresh()->title);
        $this->assertEquals('category on target instance', $screenCategory->refresh()->name);
    }

    public function testCopyImportOption()
    {
        list($payload, $screen, $screenCategory) = $this->createScreen();

        $options = new Options([
            $screen->uuid => ['mode' => 'copy'],
            $screenCategory->uuid => ['mode' => 'discard'],
        ]);
        (new Importer($payload, $options))->doImport();

        // Does not change
        $this->assertEquals('screen on target instance', $screen->refresh()->title);
        $this->assertDatabaseHas('screens', ['title' => 'exported screen']);

        $this->assertEquals('category on target instance', $screen->categories[0]->name);
        $importedScreen = Screen::where('title', 'exported screen')->firstOrFail();
        // Copied screen should NOT have the category on the original
        $this->assertCount(0, $importedScreen->categories);
        $this->assertNotEquals($screen->uuid, $importedScreen->uuid);
    }

    private function createScreen()
    {
        $screen = Screen::factory()->create(['title' => 'exported screen']);
        $screenCategory = ScreenCategory::factory()->create(['name' => 'exported category']);
        $screen->screen_category_id = $screenCategory->id;
        $screen->save();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $screen->update(['title' => 'screen on target instance']);
        $screenCategory->update(['name' => 'category on target instance']);

        return [$payload, $screen, $screenCategory];
    }

    public function testLastModifiedBy()
    {
        $this->addGlobalSignalProcess();
        $process = Process::factory()->create();
        $latestProcessVersion = $process->getLatestVersion();
        $lastUpdateUser = User::factory()->create([
            'firstname'=>'Bob',
            'lastname'=>'The Builder',
        ]);
        $versionHistory = VersionHistory::factory()->create([
            'user_id'=>$lastUpdateUser->id,
            'versionable_id'=>$latestProcessVersion->id,
            'versionable_type'=> ProcessVersion::class,
        ]);

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $lastModifiedBy = $payload['export'][$process->uuid]['last_modified_by'];
        $this->assertEquals('Bob The Builder', $lastModifiedBy);
    }

    public function testGetProcessManager()
    {
        $this->addGlobalSignalProcess();
        $managerUser = User::factory()->create([
            'firstname'=>'John',
            'lastname'=>'Doe',
        ]);

        $process = Process::factory()->create([
            'manager_id'=> $managerUser->id,
        ]);

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $processManager = $payload['export'][$process->uuid]['process_manager'];
        $this->assertEquals('John Doe', $processManager);
    }
}
