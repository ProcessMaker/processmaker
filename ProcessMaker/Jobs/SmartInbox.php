<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Facades\ProcessMaker\InboxRules\ApplyAction;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Models\ProcessRequestToken;

class SmartInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $incomingTaskId;
    /**
     * Create a new job instance.
     */
    public function __construct(int $incomingTaskId)
    { 
        $this->incomingTaskId = $incomingTaskId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {   
        $incomingTask = ProcessRequestToken::findOrFail($this->incomingTaskId);
        $matchResult = MatchingTasks::matchingInboxRules($incomingTask);
        if($matchResult){
            //Here calls ApplyAction Class
            ApplyAction::applyActionOnTask($incomingTask);
        }
    }
}
