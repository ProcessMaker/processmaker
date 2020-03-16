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
use ProcessMaker\Nayra\Bpmn\Models\SignalEventDefinition;
use ProcessMaker\Nayra\Contracts\Bpmn\SignalEventDefinitionInterface;
use Throwable;

class CatchSignalEvent implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    private const maxJobs = 10;

    public $eventDefinition;
    public $payload;
    public $requestId;
    public $signalRef;
    public $throwEvent;
    public $tokenId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ThrowEventInterface $throwEvent, SignalEventDefinition $sourceEventDefinition, TokenInterface $token)
    {
        $this->eventDefinition = $sourceEventDefinition->getId();
        $this->payload = $token->processRequest->data;
        $this->requestId = $token->processRequest->getKey();
        $this->signalRef = $sourceEventDefinition->getProperty('signalRef');
        $this->throwEvent = $throwEvent->getId();
        $this->tokenId = $token->getKey();
    }

    public function handle()
    {
        $processes = Process::whereJsonContains('signal_events', $this->signalRef);
        $count = ProcessRequest::whereJsonContains('signal_events', $this->signalRef)
            ->where('status', 'ACTIVE')
            ->where('id', '!=', $this->requestId)
            ->count();
        //dump(['requestId' => $this->requestId], ProcessRequest::all()->toArray(), $count);
        if ($count) {
            $perJob = ceil($count / self::maxJobs);
            $requests = ProcessRequest::select(['id'])
                ->whereJsonContains('signal_events', $this->signalRef)
                ->where('status', 'ACTIVE')
                ->where('id', '!=', $this->requestId)
                ->orderBy('id')
                ->pluck('id')
                ->toArray();
            $chuncks = array_chunk($requests, $perJob);
            foreach ($chuncks as $chunck) {
                CatchSignalEventRequest::dispatch(
                    $chunck,
                    $this->signalRef,
                    $this->payload,
                    $this->throwEvent,
                    $this->eventDefinition,
                    $this->tokenId,
                    $this->requestId
                );
            }
        }
    }
}
