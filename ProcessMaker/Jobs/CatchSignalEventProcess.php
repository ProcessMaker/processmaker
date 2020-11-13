<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\Repositories\DefinitionsRepository;

class CatchSignalEventProcess implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $payload;
    public $processId;
    public $signalRef;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($processId, $signalRef, $payload)
    {
        $this->payload = $payload;
        $this->processId = $processId;
        $this->signalRef = $signalRef;
    }

    public function handle()
    {
        $repository = new DefinitionsRepository;
        $eventDefinition = $repository->createSignalEventDefinition();
        $signal = $repository->createSignal();
        $signal->setId($this->signalRef);
        $eventDefinition->setPayload($signal);
        $eventDefinition->setProperty('signalRef', $this->signalRef);

        $version = Process::find($this->processId)->getLatestVersion();
        $definitions = $version->getDefinitions(true, null, false);
        $engine = $definitions->getEngine();
        $engine->loadProcessDefinitions($definitions);
        $engine->getEventDefinitionBus()->dispatchEventDefinition(
            null,
            $eventDefinition,
            null
        );

        if ($this->payload) {
            $catches = SignalManager::getSignalCatchEvents($this->signalRef, $definitions);
            $processVariable = '';
            foreach($catches as $catch) {
                $processVariable = $definitions->getStartEvent($catch['id'])->getBpmnElement()->getAttribute('pm:config');
            }
            if ($processVariable) {
                foreach ($engine->getExecutionInstances() as $instance) {
                    $instance->getDataStore()->putData($processVariable, $this->payload);
                }
            }
            else {
                foreach ($this->payload as $key => $value) {
                    foreach ($engine->getExecutionInstances() as $instance) {
                        $instance->getDataStore()->putData($key, $value);
                    }
                }
            }
        }

        $engine->runToNextState();
    }

    /**
     * Get event definition for the signal event
     *
     * @param BpmnDocument $definitions
     *
     * @return SignalEventDefinitionInterface
     */
    private function getEventDefinitionBySignalRef(BpmnDocument $definitions)
    {
        $eventDefinitions = $definitions->findElementById($this->throwEvent)->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'signalEventDefinition');
        foreach ($eventDefinitions as $node) {
            if ($node->getAttribute('signalRef') === $this->signalRef) {
                return $node->getBpmnElementInstance();
            }
        }
    }
}
