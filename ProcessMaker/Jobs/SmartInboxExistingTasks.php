<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use Facades\ProcessMaker\InboxRules\ApplyAction;

class SmartInboxExistingTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $inboxRuleId;
    /**
     * Create a new job instance.
     */
    public function __construct(int $inboxRuleId)
    {
        $this->inboxRuleId = $inboxRuleId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $matchingTasks = MatchingTasks::get($this->inboxRuleId);
        foreach ($matchingTasks as $task) {
            ApplyAction::applyActionOnTask($task);
        }
    }
}
