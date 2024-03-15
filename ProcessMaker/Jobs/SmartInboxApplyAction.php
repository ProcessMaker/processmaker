<?php

namespace ProcessMaker\Jobs;

use Facades\ProcessMaker\InboxRules\ApplyAction;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessRequestToken;

class SmartInboxApplyAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $taskId,
        public int $inboxRuleId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inboxRule = InboxRule::findOrFail($this->inboxRuleId);
        $task = ProcessRequestToken::findOrFail($this->taskId);
        ApplyAction::applyActionOnTask($task, $inboxRule);
    }
}
