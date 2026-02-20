<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\EvaluateProcessRetentionJob;
use ProcessMaker\Models\Process;

class EvaluateCaseRetention extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:retention:evaluate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate and delete cases past their retention period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Only run if case retention policy is enabled
        $enabled = config('app.case_retention_policy_enabled', false);
        if (!$enabled) {
            $this->info('Case retention policy is disabled');
            $this->error('Skipping case retention evaluation');

            return;
        }

        $this->info('Case retention policy is enabled');
        $this->info('Dispatching retention evaluation jobs for all processes');

        // Process all processes when retention policy is enabled
        // Processes without retention_period will default to 1_year
        $jobCount = 0;
        Process::chunkById(100, function ($processes) use (&$jobCount) {
            foreach ($processes as $process) {
                dispatch(new EvaluateProcessRetentionJob($process->id));
                $jobCount++;
            }
        });

        $this->info("Dispatched {$jobCount} retention evaluation job(s) to the queue");
        $this->info('Jobs will be processed asynchronously by queue workers');
    }
}
