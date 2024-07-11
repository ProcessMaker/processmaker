<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\RecommendationEngine;

class SmartInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $incomingTaskId;

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

        if (!$incomingTask->user_id) {
            return;
        }

        $matchingInboxRules = app(MatchingTasks::class)->matchingInboxRules($incomingTask);

        foreach ($matchingInboxRules as $inboxRule) {
            SmartInboxApplyAction::dispatchSync($this->incomingTaskId, $inboxRule->id);
        }

        // Only generate recommendations after inbox rules have been applied.
        if (RecommendationEngine::shouldGenerateFor($incomingTask->user)) {
            GenerateUserRecommendations::dispatch($incomingTask->user_id);
        }
    }
}
