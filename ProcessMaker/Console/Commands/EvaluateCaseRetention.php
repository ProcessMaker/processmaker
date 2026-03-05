<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\EvaluateProcessRetentionJob;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

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

        // Get system category IDs to exclude
        $systemCategoryIds = ProcessCategory::where('is_system', true)->pluck('id');

        // Exclude processes that are templates or in system categories
        $jobCount = 0;
        $query = Process::where('is_template', '!=', 1);

        // Exclude processes in system categories
        if ($systemCategoryIds->isNotEmpty()) {
            $query->where(function ($q) use ($systemCategoryIds) {
                $q->where(function ($subQuery) use ($systemCategoryIds) {
                    $subQuery->whereNotIn('process_category_id', $systemCategoryIds)
                        ->orWhereNull('process_category_id');
                });
            })
            ->whereDoesntHave('categories', function ($q) use ($systemCategoryIds) {
                // Exclude processes with any category assignment to system categories
                $q->whereIn('process_categories.id', $systemCategoryIds);
            });
        }

        $query->chunkById(100, function ($processes) use (&$jobCount) {
            foreach ($processes as $process) {
                dispatch(new EvaluateProcessRetentionJob($process->id));
                $jobCount++;
            }
        });

        $this->info("Dispatched {$jobCount} retention evaluation job(s) to the queue");
        $this->info('Jobs will be processed asynchronously by queue workers');
    }
}
