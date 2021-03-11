<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class ThrowSignalEvent implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    private const maxJobs = 10;

    public $signalRef;
    public $data;
    public $excludeProcesses;
    public $excludeRequests;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($signalRef, array $data = [], array $excludeProcesses = [], array $excludeRequests = [])
    {
        $this->signalRef = $signalRef;
        $this->data = $data;
        $this->excludeProcesses = $excludeProcesses;
        $this->excludeRequests = $excludeRequests;
    }

    public function handle()
    {
        $processes = Process::whereNotIn('id', $this->excludeProcesses)
            ->whereJsonContains('signal_events', $this->signalRef)
            ->pluck('id')
            ->toArray();
        foreach ($processes as $process) {
            CatchSignalEventProcess::dispatch(
                $process,
                $this->signalRef,
                $this->data
            )->onQueue('bpmn');
        }
        $count = ProcessRequest::whereNotIn('id', $this->excludeRequests)
            ->where('status', 'ACTIVE')
            ->whereJsonContains('signal_events', $this->signalRef)
            ->count();
        if ($count) {
            $perJob = ceil($count / self::maxJobs);
            $requests = ProcessRequest::select(['id'])
                ->whereJsonContains('signal_events', $this->signalRef)
                ->where('status', 'ACTIVE')
                ->whereNotIn('id', $this->excludeRequests);
            $requests = $requests->orderBy('id')
                ->pluck('id')
                ->toArray();
            $chuncks = array_chunk($requests, $perJob);
            foreach ($chuncks as $chunck) {
                CatchSignalEventRequest::dispatch(
                    $chunck,
                    $this->signalRef,
                    $this->data
                )->onQueue('bpmn');
            }
        }
    }
}
