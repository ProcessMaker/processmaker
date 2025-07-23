<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScheduledJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct($config)
    {
        \Log::info('ScheduledJob dispatched' . json_encode($config));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
