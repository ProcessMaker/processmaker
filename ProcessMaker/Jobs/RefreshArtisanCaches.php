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
        // Do not rebuild the cache and restart the queue any more.
        // This is no longer needed.
    }
}
