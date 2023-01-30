<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Exporters\UserExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SignalExporterTest extends TestCase
{
    use HelperTrait;

    public function test()
    {
        DB::beginTransaction();

        $this->addGlobalSignalProcess();

        $signal = new SignalData('test_some_global', 'Test Global Signal', 'some global description');
        SignalManager::addSignal($signal);

        $bpmn = file_get_contents(__DIR__ . '/../fixtures/process-with-signals.bpmn.xml');
        $processWithSignals = Process::factory()->create(['name' => 'process with signals', 'bpmn' => $bpmn]);

        $payload = $this->export($processWithSignals, ProcessExporter::class);

        DB::rollBack(); // Delete all created items since DB::beginTransaction

        $this->addGlobalSignalProcess();

        $this->import($payload);

        // Assert global signal was inserted into global signal process
        $signals = app()->make(SignalHelper::class)->getGlobalSignals();
        $this->assertEquals('Test Global Signal', $signals['test_some_global']);
    }

    public function testExcludeGlobalSignalsFromExport()
    {
        DB::beginTransaction();
        $this->addGlobalSignalProcess();

        $signal = new SignalData('test_some_global', 'Test Global Signal', 'some global description');
        SignalManager::addSignal($signal);

        $bpmn = file_get_contents(__DIR__ . '/../fixtures/process-with-signals.bpmn.xml');
        $processWithSignals = Process::factory()->create(['name' => 'process with signals', 'bpmn' => $bpmn]);

        $options = new Options([
            'signal-test_some_global' => ['mode' => 'discard'],
            'signal-test_some_local'  => ['mode' => 'discard'],
        ]);
        $payload = $this->export($processWithSignals, ProcessExporter::class, $options);

        DB::rollBack(); // Delete all created items since DB::beginTransaction

        $this->addGlobalSignalProcess();

        $this->import($payload);

        // Assert that the signalEventDefinition tag inside intermediateCatchEvent has no attributes
        $importedProcess = Process::where('name', 'process with signals')->firstOrFail();
        $definitions = $importedProcess->getDefinitions(true);
        $signalEventDefinitions = $definitions->getElementsByTagName('signalEventDefinition');
        $this->assertEquals(2, $signalEventDefinitions->count());
        $this->assertEquals(0, $signalEventDefinitions[0]->attributes->length);
        $this->assertEquals(0, $signalEventDefinitions[1]->attributes->length);

        // Assert signal definition is not present in the imported process
        $this->assertEquals(0, $definitions->getElementsByTagName('signal')->count());

        // Assert global signal was not added to global signal process
        $globalSignals = app()->make(SignalHelper::class)->getGlobalSignals();
        $this->assertArrayNotHasKey('test_some_global', $globalSignals);
    }
}
