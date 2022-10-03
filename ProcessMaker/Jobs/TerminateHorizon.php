<?php

namespace ProcessMaker\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TerminateHorizon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Number of tries to run this job
     *
     * @var int
     */
    public $tries = 1;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $exitCode = Artisan::call('horizon:terminate', [
            '--no-interaction' => true,
        ]);

        Log::info('Horizon Restart Attempted', [
            'exit_code' => $exitCode,
            'command_output' => Artisan::output(),
        ]);
    }
}

