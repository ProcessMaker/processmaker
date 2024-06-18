<?php

namespace ProcessMaker\Jobs;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\SyncRecommendations;

class SyncReccomendationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SyncRecommendations $syncRecommendations;

    public function __construct(SyncRecommendations $syncRecommendations)
    {
        $this->syncRecommendations = $syncRecommendations;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->syncRecommendations->sync();
    }
}
