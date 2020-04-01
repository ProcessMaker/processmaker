<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Contracts\Bpmn\SignalEventDefinitionInterface;
use ProcessMaker\Repositories\BpmnDocument;

class CatchSignalEventRequest implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $chunck;
    public $signalRef;
    public $payload;
    public $throwEvent;
    public $eventDefinition;
    public $tokenId;
    public $requestId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chunck, $signalRef, $payload, $throwEvent, $eventDefinition, $tokenId, $requestId)
    {
        $this->chunck = $chunck;
        $this->payload = $payload;
        $this->signalRef = $signalRef;
        $this->throwEvent = $throwEvent;
        $this->eventDefinition = $eventDefinition;
        $this->tokenId = $tokenId;
        $this->requestId = $requestId;
    }

    public function handle()
    {
        $mainRequest = ProcessRequest::find($this->requestId);
        $definitions = ($mainRequest->processVersion ?? $mainRequest->process)->getDefinitions(true);
        $throwEvent = $definitions->findElementById($this->throwEvent)->getBpmnElementInstance();
        $eventDefinition = $this->getEventDefinitionBySignalRef($definitions);
        $token = ProcessRequestToken::find($this->tokenId);
        foreach ($this->chunck as $requestId) {
            $request = ProcessRequest::find($requestId);
            $definitions = ($request->processVersion ?? $request->process)->getDefinitions(true, null, false);
            $engine = $definitions->getEngine();
            $engine->loadProcessRequest($request);
            $engine->getEventDefinitionBus()->dispatchEventDefinition(
                $throwEvent,
                $eventDefinition,
                $token
            );
            $engine->runToNextState();
        }
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
