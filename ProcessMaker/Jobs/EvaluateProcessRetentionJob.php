<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\Process;

class EvaluateProcessRetentionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $processId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $process = Process::find($this->processId);
        if (!$process) {
            Log::error('CaseRetentionJob: Process not found', ['process_id' => $this->processId]);

            return;
        }

        $retentionMonths = match ($process->properties['retention_period']) {
            '6_months' => 6,
            '1_year' => 12,
            '3_years' => 36,
            '5_years' => 60,
        };

        $cutoffDate = $process->retention_updated_at->addMonths($retentionMonths);

        CaseNumber::where('process_id', $this->processId)
            ->where('created_at', '<', $cutoffDate)
            ->chunkById(100, function ($cases) {
                $caseIds = $cases->pluck('id');
                // Delete the cases
                CaseNumber::whereIn('id', $caseIds)->delete();

                // TODO: Add logs to track the number of cases deleted
                // Get deleted timestamp
                // $deletedAt = Carbon::now();
                // RetentionPolicyLog::record($process->id, $caseIds, $deletedAt);
            });
    }
}
