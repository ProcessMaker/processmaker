<?php

namespace ProcessMaker\Jobs;

use Facades\ProcessMaker\InboxRules\ApplyAction;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use Facades\ProcessMaker\Models\InboxRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SmartInboxExistingTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $inboxRuleId;

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
        //Load InboxRule by ID
        $inboxRule = InboxRule::findOrFail($this->inboxRuleId);

        $matchingTasks = MatchingTasks::get($inboxRule);
        foreach ($matchingTasks as $task) {
            ApplyAction::applyActionOnTask($task, $inboxRule);
        }
    }
}
