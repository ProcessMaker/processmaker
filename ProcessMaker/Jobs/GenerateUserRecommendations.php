<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\User;
use ProcessMaker\RecommendationEngine;

class GenerateUserRecommendations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User|int $user)
    {
        $this->user = $user instanceof User ? $user : User::findOrFail($user);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        RecommendationEngine::for($this->user)->generate();
    }
}
