<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\BpmnDocument;

class CatchSignalEventProcess implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $eventDefinition;
    public $payload;
    public $processId;
    public $requestId;
    public $signalRef;
    public $throwEvent;
    public $tokenId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($processId, $signalRef, $payload, $throwEvent, $eventDefinition, $tokenId, $requestId)
    {
        $this->eventDefinition = $eventDefinition;
        $this->payload = $payload;
        $this->processId = $processId;
        $this->requestId = $requestId;
        $this->signalRef = $signalRef;
        $this->throwEvent = $throwEvent;
        $this->tokenId = $tokenId;
    }

    public function handle()
    {
        $mainRequest = ProcessRequest::find($this->requestId);
        if ($mainRequest) {
            $definitions = ($mainRequest->processVersion ?? $mainRequest->process)->getDefinitions(true);
            $engine = $definitions->getEngine();
            $throwEvent = $definitions->findElementById($this->throwEvent)->getBpmnElementInstance();
            $instance = $engine->loadProcessRequest($mainRequest);
            $eventDefinition = $this->getEventDefinitionBySignalRef($definitions);
            $token = ProcessRequestToken::find($this->tokenId);
            if ($token) {$token->setInstance($instance);}
        }
        else {
            $throwEvent = null;
            $instance = null;
            $eventDefinition = $this->eventDefinition;
            $token = null;
        }
        $version = Process::find($this->processId)->getLatestVersion();
        $definitions = $version->getDefinitions(true, null, false);
        $engine = $definitions->getEngine();
        $engine->loadProcessDefinitions($definitions);
        $engine->getEventDefinitionBus()->dispatchEventDefinition(
            $throwEvent,
            $eventDefinition,
            $token
        );

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
