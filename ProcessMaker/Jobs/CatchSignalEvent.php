<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use Throwable;

class CatchSignalEvent implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    private const maxJobs = 10;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ThrowEventInterface $source, EventDefinitionInterface $sourceEventDefinition, TokenInterface $token)
    {

    }

    public function handler()
    {
        //$processes = Process::whereJsonContains('signal_events', $this->signalRef);
        $count = ProcessRequest::whereJsonContains('signal_events', $this->signalRef)->count();
        $perJob = ceil($count / self::maxJobs);
        $requests = ProcessRequest::select(['id'])->whereJsonContains('signal_events', $this->signalRef)->orderBy('id');
    }
}
