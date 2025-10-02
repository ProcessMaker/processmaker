<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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
     * Debounce when multiple Settings are saved at the same time
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('refresh_artisan_caches'))->dontRelease()];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Skip in testing environment because this reconnects the database
        // meaning we loose transactions, and sets the console output verbosity
        // to quiet so we loose expectsOutput assertions.
        if (app()->environment('testing')) {
            return;
        }

        $options = [
            '--no-interaction' => true,
            '--quiet' => true,
        ];

        if (app()->configurationIsCached()) {
            Artisan::call('config:cache', $options);
        }
        Artisan::call('queue:restart', $options);
    }
}
