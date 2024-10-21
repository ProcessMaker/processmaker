<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ProcessLaunchpadExporterTest extends TestCase
{
    use HelperTrait;

    private function fixtures(): array
    {
        $cancelScreen = $this->createScreen('basic-form-screen', ['title' => 'Cancel Screen']);
        $requestDetailScreen = $this->createScreen('basic-display-screen', ['title' => 'Request Detail Screen']);
        $user = User::factory()->create(['username' => 'testuser']);
        $process = $this->createProcess('basic-process', [
            'name' => 'Process',
            'user_id' => $user->id,
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
        ]);

        $savedSearch1 = SavedSearch::factory()->create(
            ['title' => 'Saved Search For Tab', 'user_id' => $user->id]
        );
        $savedSearch2 = SavedSearch::factory()->create(
            ['title' => 'Another Saved Search For Tab', 'user_id' => $user->id]
        );

        $launchpad = ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'properties' => json_encode([
                'icon' => 'Alarm',
                'icon_label' => 'Alarm',
                'screen_id' => $cancelScreen->id,
                'screen_uuid' => $cancelScreen->uuid,
                'screen_title' => 'Cancel Screen',
                'tabs' => [
                    ['idSavedSearch' => $savedSearch1->id],
                    ['idSavedSearch' => $savedSearch2->id],
                ],
            ]),
        ]);

        return [
            'process' => $process,
            'user' => $user,
            'launchpad' => $launchpad,
            'savedSearch1' => $savedSearch1,
            'savedSearch2' => $savedSearch2,
        ];
    }

    public function testExport()
    {
        $this->addGlobalSignalProcess();
        [
            'process' => $process,
            'launchpad' => $launchpad,
        ] = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $processDependents = Arr::get($payload, 'export.' . $process->uuid . '.dependents');
        $processDependentUuids = Arr::pluck($processDependents, 'uuid');

        $this->assertContains($process->category->uuid, $processDependentUuids);
        $this->assertContains($launchpad->uuid, $processDependentUuids);
    }

    public function testImportWithCopy()
    {
        $this->addGlobalSignalProcess();
        [
            'process' => $process,
            'launchpad' => $launchpad,
            'savedSearch1' => $savedSearch1,
            'savedSearch2' => $savedSearch2,
        ] = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $originalSavedSearch1Id = $savedSearch1->id;
        $savedSearch1->delete();

        $optionsArray[$process->uuid] = ['mode' => 'copy', 'saveAssetsMode' => 'saveAllAssets', 'discardedByParent' => false];
        $optionsArray[$savedSearch2->uuid] = ['mode' => 'copy', 'saveAssetsMode' => 'saveAllAssets', 'discardedByParent' => false];
        $optionsArray[$launchpad->uuid] = ['mode' => 'copy', 'saveAssetsMode' => 'saveAllAssets', 'discardedByParent' => false];

        $options = new Options($optionsArray);
        $importer = new Importer($payload, $options);

        $importer->doImport();

        $newProcess = Process::where('name', 'Process 2')->first();
        $this->assertNotNull($newProcess->launchpad);

        // Assert savedSearch1 has a new ID and that savedSearch2 has been copied
        $savedSearch1 = SavedSearch::where('title', 'Saved Search For Tab')->firstOrFail();
        $savedSearch2 = SavedSearch::where('title', 'Another Saved Search For Tab 2')->firstOrFail();

        $properties = json_decode($newProcess->launchpad->properties, true);
        $newSavedSearch1Id = Arr::get($properties, 'tabs.0.idSavedSearch');
        $newSavedSearch2Id = Arr::get($properties, 'tabs.1.idSavedSearch');

        $this->assertNotEquals($originalSavedSearch1Id, $savedSearch1->id);
        $this->assertEquals($savedSearch1->id, $newSavedSearch1Id);
        $this->assertEquals($savedSearch2->id, $newSavedSearch2Id);

        // Re-import the same process.
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $newProcess = Process::where('name', 'Process 3')->first();
        $this->assertNotNull($newProcess->launchpad);
    }
}
