<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\Repositories\DefinitionsRepository;

class CatchSignalEventInRequest extends BpmnAction implements ShouldQueue
{
    public $definitionsId;

    public $instanceId;

    public $tokenId;

    public $data;

    public $signalRef;

    public $disableGlobalEvents;

    /**
     * Create a new job instance.
     *
     * @param  \ProcessMaker\Models\ProcessRequest  $instance
     * @param  array  $data
     * @param  string  $signalRef
     */
    public function __construct(ProcessRequest $instance, array $data, $signalRef)
    {
        $this->instanceId = $instance->getKey();
        $this->data = $data;
        $this->signalRef = $signalRef;
        // Disable global events for this job to do not repeat the same signal
        $this->disableGlobalEvents = true;
    }

    /**
     * Dispatch the signal event into de request $instance
     *
     * @return void
     */
    public function action(ProcessRequest $instance, BpmnDocument $definitions)
    {
        // Prepare the signal
        $repository = new DefinitionsRepository;
        $eventDefinition = $repository->createSignalEventDefinition();
        $signal = $repository->createSignal();
        $signal->setId($this->signalRef);
        $eventDefinition->setPayload($signal);
        $eventDefinition->setProperty('signalRef', $this->signalRef);
        // Do not triggers signal start events with this signal (only intermediate and boundary events) See CatchSignalEventProcess
        $eventDefinition->setDoNotTriggerStartEvents(true);

        // Dispatch the signal to the engine
        $this->engine->getEventDefinitionBus()->dispatchEventDefinition(
            null,
            $eventDefinition,
            null
        );

        // Get all the catch events defined in the $definitions
        $catches = SignalManager::getSignalCatchEvents($this->signalRef, $definitions);
        foreach ($catches as $catch) {
            $catchEvent = $definitions->getElementInstanceById($catch['id']);
            if ($catchEvent->getTokens($instance)->count() < 1) {
                continue;
            }
            // Put the payload into the configured variable of each catch event
            // if not defined in the variable, put the payload into the root request data
            $processVariable = $catchEvent->getBpmnElement()->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config');
            if ($processVariable) {
                $instance->getDataStore()->putData($processVariable, $this->data);
            } else {
                if ($this->data) {
                    foreach ($this->data as $key => $value) {
                        $instance->getDataStore()->putData($key, $value);
                    }
                }
            }
        }
    }
}
