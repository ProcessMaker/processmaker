<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

class RefreshArtisanCaches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('clear-compiled', $options = [
            '--no-interaction' => true,
            '--quiet' => true,
            '--env' => app()->environment(),
        ]);

        if (app()->routesAreCached()) {
            Artisan::call('route:cache', $options);
        }

        if (app()->eventsAreCached()) {
            Artisan::call('event:cache', $options);
        }

        if (app()->configurationIsCached()) {
            Artisan::call('config:cache', $options);
        } else {
            Artisan::call('queue:restart', $options);
        }
    }
}
