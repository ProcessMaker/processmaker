<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Console\Application;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class RefreshArtisanCaches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

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
            // Run in a separate process to avoid the tenant being set.
            // We do not use a tenant-specific config cache file.
            Process::path(base_path())
                ->env(['TENANT' => false, 'APP_URL' => false])
                ->run(Application::formatCommandString('config:cache'))->throw();
        } else {
            Artisan::call('queue:restart', $options);

            // We call this manually here since this job is dispatched
            // automatically when the config *is* cached
            RestartMessageConsumers::dispatchSync();
        }
    }
}
