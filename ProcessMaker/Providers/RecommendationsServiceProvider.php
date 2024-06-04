<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Event;
use ProcessMaker\SyncRecommendations;
use ProcessMaker\RecommendationEngine;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Events\ActivityCompleted;
use ProcessMaker\Jobs\GenerateUserRecommendations;

class RecommendationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SyncRecommendations::class, function ($app) {
            return new SyncRecommendations();
        });

        // The ActivityAssigned event is not listened for since we dispatch the
        // GenerateUserRecommendations job at the end of the SmartInbox job.
        Event::listen(ActivityCompleted::class, function ($event) {
            // Without relations to prevent huge sets of unnecessary
            // data from being serialized and passed to the job
            $processRequestToken = $event->getProcessRequestToken()->withoutRelations();

            // Dispatch the job to the low-priority queue
            GenerateUserRecommendations::dispatch($processRequestToken->user_id)->onQueue('low');
        });
    }
}
