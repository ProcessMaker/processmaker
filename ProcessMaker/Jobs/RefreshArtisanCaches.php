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
        $options = [
            '--no-interaction' => true,
            '--quiet' => true,
        ];

        if (app()->configurationIsCached()) {
            Artisan::call('config:cache', $options);
        } else {
            Artisan::call('queue:restart', $options);

            // We call this manually here since this job is dispatched
            // automatically when the config *is* cached
            RestartMessageConsumers::dispatchSync();
        }
    }
}
