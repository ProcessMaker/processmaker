<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Jobs\GenerateUserRecommendations;

class RecommendationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Event::listen(ActivityAssigned::class, function (ActivityAssigned $event) {
            // Without relations to prevent huge sets of unnecessary
            // data from being serialized and passed to the job
            $processRequestToken = $event->getProcessRequestToken()->withoutRelations();

            // Dispatch the job to the low-priority queue
            GenerateUserRecommendations::dispatch($processRequestToken)->onQueue('low');
        });
    }
}
