<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;

class CatchSignalEventRequest implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $chunck;

    public $signalRef;

    public $payload_uid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chunck, $signalRef, $payload)
    {
        $this->chunck = $chunck;
        $this->payload_uid = packTemporalData($payload);
        $this->signalRef = $signalRef;
    }

    public function handle()
    {
        $payload = unpackTemporalData($this->payload_uid);
        foreach ($this->chunck as $requestId) {
            $request = ProcessRequest::find($requestId);
            WorkflowManager::throwSignalEventRequest($request, $this->signalRef, $payload);
        }
        removeTemporalData($this->payload_uid);
    }
}
