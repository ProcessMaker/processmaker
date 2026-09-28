<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MoveScriptExecutorToMicroservice implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $uuid)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Artisan::call('processmaker:transition-executors --uuid=' . $this->uuid );
    }
}
