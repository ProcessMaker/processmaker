<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

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
        // Only run if case retention policy is enabled
        // Use getenv() to read directly from environment (works better in tests)
        $enabled = getenv('CASE_RETENTION_POLICY_ENABLED');
        if ($enabled === false || $enabled === 'false' || $enabled === '0' || $enabled === '') {
            return;
        }

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

        $retentionUpdatedAt = Carbon::parse($process->properties['retention_updated_at']);

        // Get all process request IDs for this process
        $processRequestIds = ProcessRequest::where('process_id', $this->processId)->pluck('id');

        // If there are no process requests, nothing to delete
        if ($processRequestIds->isEmpty()) {
            return;
        }

        // Handle two scenarios:
        // 1. Cases created BEFORE retention_updated_at: Delete if older than retention period from retention_updated_at
        //    (These cases were subject to the old retention policy, but we apply current retention from update date)
        // 2. Cases created AFTER retention_updated_at: Delete if older than retention period from their creation date
        //    (These cases are subject to the new retention policy)

        $now = Carbon::now();

        // For cases created before retention_updated_at: cutoff is retention_updated_at - retention_period
        $oldCasesCutoff = $retentionUpdatedAt->copy()->subMonths($retentionMonths);

        // For cases created after retention_updated_at: cutoff is now - retention_period
        $newCasesCutoff = $now->copy()->subMonths($retentionMonths);

        CaseNumber::whereIn('process_request_id', $processRequestIds)
            ->where(function ($query) use ($retentionUpdatedAt, $oldCasesCutoff, $newCasesCutoff) {
                // Cases created before retention_updated_at: delete if created before (retention_updated_at - retention_period)
                $query->where(function ($q) use ($retentionUpdatedAt, $oldCasesCutoff) {
                    $q->where('created_at', '<', $retentionUpdatedAt)
                    ->where('created_at', '<', $oldCasesCutoff);
                })
                // Cases created after retention_updated_at: delete if created before (now - retention_period)
                ->orWhere(function ($q) use ($retentionUpdatedAt, $newCasesCutoff) {
                    $q->where('created_at', '>=', $retentionUpdatedAt)
                    ->where('created_at', '<', $newCasesCutoff);
                });
            })
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
