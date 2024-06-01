<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\InboxRules\MatchingTasks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequestToken;

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

        $matchingInboxRules = app(MatchingTasks::class)->matchingInboxRules($incomingTask);

        foreach ($matchingInboxRules as $inboxRule) {
            SmartInboxApplyAction::dispatchSync($this->incomingTaskId, $inboxRule->id);
        }

        GenerateUserRecommendations::dispatch($incomingTask->user_id)->onQueue('low');
    }
}
