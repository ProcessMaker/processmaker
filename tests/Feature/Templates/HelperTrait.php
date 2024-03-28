<?php

namespace Tests\Feature\Templates;

use Database\Seeders\SignalSeeder;
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

    public function export($model, $exporterClass, $options = null)
    {
        $exporter = new Exporter();
        $exporter->export($model, $exporterClass, $options);

        return $exporter->payload();
    }

    public function import($payload, $options = null)
    {
        $options = $options ?: new Options([]);
        $importer = new Importer($payload, $options);
        $importer->previewImport();
        $importer->doImport();
    }
}
