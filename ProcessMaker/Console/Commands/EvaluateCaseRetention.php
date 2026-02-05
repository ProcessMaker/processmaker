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
        $this->info('Evaluating and deleting cases past their retention period');

        Process::whereNotNull('properties->retention_period')->chunkById(100, function ($processes) {
            foreach ($processes as $process) {
                dispatch(new EvaluateProcessRetentionJob($process->id));
            }
        });

        $this->info('Cases retention evaluation complete');
    }
}
