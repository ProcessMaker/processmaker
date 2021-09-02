<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\ProcessRequest;

class CatchSignalEventRequest implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $chunck;
    public $signalRef;
    public $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chunck, $signalRef, $payload)
    {
        $this->chunck = $chunck;
        $this->payload = $payload;
        $this->signalRef = $signalRef;
    }

    public function handle()
    {
        foreach ($this->chunck as $requestId) {
            $request = ProcessRequest::find($requestId);
            CatchSignalEventInRequest::dispatchNow($request, $this->payload, $this->signalRef);
        }
    }
}
