<?php

namespace ProcessMaker\Jobs;

use Facades\ProcessMaker\ApplyRecommendation as RunApplyRecommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;

class ApplyRecommendation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $action,
        public int $recommendationId,
        public int $userId,
        public array $params = [])
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $recommendation = Recommendation::findOrFail($this->recommendationId);
        $user = User::findOrFail($this->userId);
        RunApplyRecommendation::run($this->action, $recommendation, $user, $this->params);
    }
}
