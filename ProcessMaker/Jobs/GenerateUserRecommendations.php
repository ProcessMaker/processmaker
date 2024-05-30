<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use ProcessMaker\Models\User;
use ProcessMaker\Filters\Filter;
use ProcessMaker\RecommendationEngine;
use ProcessMaker\Models\Recommendation;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequestToken;

class GenerateUserRecommendations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ProcessRequestToken $token;

    /**
     * Create a new job instance.
     */
    public function __construct(ProcessRequestToken|int $token)
    {
        if (is_int($token)) {
            $token = ProcessRequestToken::findOrFail($token)->withoutRelations();
        }

        $this->token = $token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        RecommendationEngine::for($this->token->user)->generate();
    }
}
