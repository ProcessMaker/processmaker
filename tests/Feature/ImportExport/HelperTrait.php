<?php

namespace Tests\Feature\ImportExport;

use Database\Seeders\SignalSeeder;
use Illuminate\Http\UploadedFile;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;

trait HelperTrait
{
    public $globalSignal = null;

    public function createProcess($bpmnPath, $attrs = [])
    {
        $bpmn = file_get_contents(__DIR__ . '/fixtures/' . $bpmnPath . '.bpmn.xml');

        return Process::factory()->create(
            array_merge(
                $attrs,
                [
                    'bpmn' => $bpmn,
                ]
            )
        );
    }

    public function createScreen($screenPath, $attrs = [], $watchersPath = null)
    {
        $config = json_decode(file_get_contents(__DIR__ . '/fixtures/' . $screenPath . '.json'), true);
        $watchers = $watchersPath ? json_decode(file_get_contents(__DIR__ . '/fixtures/' . $watchersPath . '.json'), true) : [];

        return Screen::factory()->create(
            [
                ...$attrs,
                ...[
                    'config' => $config,
                    'watchers' => $watchers,
                ],
            ]
        );
    }

    public function addGlobalSignalProcess()
    {
        ProcessCategory::factory()->create(['is_system'=> true]);
        (new SignalSeeder())->run();
        $this->globalSignal = new SignalData('test_global', 'test_global', '');
        SignalManager::addSignal($this->globalSignal);
    }

    public function runExportAndImport($model, $exporterClass, $between)
    {
        $this->addGlobalSignalProcess();

        $payload = $this->export($model, $exporterClass);

        $between();

        $this->import($payload);
    }

    public function export($model, $exporterClass)
    {
        $exporter = new Exporter();
        $exporter->export($model, $exporterClass);

        return $exporter->payload();
    }

    public function import($payload)
    {
        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->doImport();
    }

    public function makeOptions($options = [])
    {
        return UploadedFile::fake()->createWithContent(
            'options.json',
            json_encode($options)
        );
    }
}
