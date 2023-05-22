<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\SignalEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * @deprecated 4.0.15 Use ThrowSignalEvent
 */
class CatchSignalEvent implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    private const maxJobs = 10;

    public $collaborationId;

    public $eventDefinition;

    public $payload;

    public $requestId;

    public $signalRef;

    public $throwEvent;

    public $tokenId;

    public $processId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ThrowEventInterface $throwEvent, SignalEventDefinitionInterface $sourceEventDefinition, TokenInterface $token)
    {
        $this->collaborationId = $token->getInstance() ? $token->getInstance()->process_collaboration_id : null;
        $this->eventDefinition = $sourceEventDefinition;
        $this->payload = $token->getInstance() ? $token->getInstance()->data : null;
        $this->requestId = $token->getInstance() ? $token->getInstance()->getId() : null;
        $event = $sourceEventDefinition->getPayload();
        $this->signalRef = $event ? $event->getId() : $sourceEventDefinition->getProperty('signalRef');
        $this->throwEvent = $throwEvent->getId();
        $token->saveToken();
        $this->tokenId = $token->getId();
        $this->processId = $token->getInstance() ? $token->getInstance()->process_id : null;
    }

    public function handle()
    {
        $processes = Process::where('id', '!=', $this->processId)
            ->whereJsonContains('signal_events', $this->signalRef)
            ->pluck('id')
            ->toArray();
        foreach ($processes as $process) {
            CatchSignalEventProcess::dispatch(
                $process,
                $this->signalRef,
                $this->payload,
                $this->throwEvent,
                $this->eventDefinition,
                $this->tokenId,
                $this->requestId
            )->onQueue('bpmn');
        }
        $count = ProcessRequest::where('status', 'ACTIVE')
            ->where('id', '!=', $this->requestId);
        if ($this->collaborationId) {
            $count = $count->where('process_collaboration_id', '!=', $this->collaborationId);
        }
        $count = $count->whereJsonContains('signal_events', $this->signalRef)
            ->count();
        if ($count) {
            $perJob = ceil($count / self::maxJobs);
            $requests = ProcessRequest::select(['id'])
                ->whereJsonContains('signal_events', $this->signalRef)
                ->where('status', 'ACTIVE')
                ->where('id', '!=', $this->requestId);
            if ($this->collaborationId) {
                $requests = $requests->where('process_collaboration_id', '!=', $this->collaborationId);
            }
            $requests = $requests->orderBy('id')
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
                )->onQueue('bpmn');
            }
        }
    }
}
