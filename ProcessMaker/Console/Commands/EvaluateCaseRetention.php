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
        $this->info('Evaluating and deleting cases past their retention period');

        // Process all processes when retention policy is enabled
        // Processes without retention_period will default to one_year
        Process::chunkById(100, function ($processes) {
            foreach ($processes as $process) {
                dispatch(new EvaluateProcessRetentionJob($process->id));
            }
        });

        $this->info('Cases retention evaluation complete');
    }
}
