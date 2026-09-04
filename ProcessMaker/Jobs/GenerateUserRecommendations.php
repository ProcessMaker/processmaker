<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;
use ProcessMaker\RecommendationEngine;

class GenerateUserRecommendations implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds the unique lock is held so duplicate dispatches for the same user are dropped.
     */
    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $user_id)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->user_id;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->user_id))->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::findOrFail($this->user_id);
        if (!RecommendationEngine::shouldGenerateFor($user)) {
            return;
        }
        RecommendationEngine::for($user)->generate();
    }
}
