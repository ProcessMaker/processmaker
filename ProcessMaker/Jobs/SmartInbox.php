<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\InboxRules\ApplyAction;
use ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessRequestToken;

class SmartInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;
    /**
     * Create a new job instance.
     */
    public function __construct(ProcessRequestToken $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inboxRule = new InboxRule();
        $matchingTasks = new MatchingTasks($inboxRule);
        $matchResult = $matchingTasks->check($this->task);
        if($matchResult){
            //Here calls ApplyAction Class
        }
    }
}
