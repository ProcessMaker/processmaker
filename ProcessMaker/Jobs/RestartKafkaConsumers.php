<?php

namespace ProcessMaker\Jobs;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RestartKafkaConsumers implements ShouldQueue
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
        if (Str::lower(config('app.message_broker_driver')) !== 'kafka') {
            return;
        }

        $exitCode = Artisan::call('kafka:restart-consumers', [
            '--no-interaction' => true,
        ]);

        Log::info('Kafka Consumer(s) Restart Attempted', [
            'exit_code' => $exitCode,
            'command_output' => Artisan::output(),
        ]);
    }
}
