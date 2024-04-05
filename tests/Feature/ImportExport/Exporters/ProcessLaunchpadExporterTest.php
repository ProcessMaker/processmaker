<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\User;
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

        $launchpad = ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'properties' => json_encode([
                'icon' => 'Alarm',
                'icon_label' => 'Alarm',
                'screen_id' => $cancelScreen->id,
                'screen_uuid' => $cancelScreen->uuid,
                'screen_title' => 'Cancel Screen',
            ]),
        ]);

        return [
            'process' => $process,
            'user' => $user,
            'launchpad' => $launchpad,
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
        ] = $this->fixtures();

        $exporter = new Exporter();
        $exporter->exportProcess($process);
        $payload = $exporter->payload();

        $optionsArray[$process->uuid] = ['mode' => 'copy'];

        $options = new Options($optionsArray);
        $importer = new Importer($payload, $options);

        $importer->doImport();

        $newProcess = Process::where('name', 'Process 2')->first();
        $this->assertNotNull($newProcess->launchpad);
    }
}
