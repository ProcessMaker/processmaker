<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequestToken;

class SmartInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $taskId;
    /**
     * Create a new job instance.
     */
    public function __construct(ProcessRequestToken $task)
    {
        $this->taskId = $task->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
