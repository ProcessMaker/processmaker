<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Bpmn\Models\SignalEventDefinition;
use ProcessMaker\Nayra\Contracts\Bpmn\SignalEventDefinitionInterface;
use Throwable;

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
        $eventDefinition = $definitions->findElementById($this->eventDefinition)->getBpmnElementInstance();
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
}
