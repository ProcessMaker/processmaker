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
        $startTime = microtime(true);

        Log::info('EvaluateProcessRetentionJob: Starting evaluation', [
            'process_id' => $this->processId,
        ]);

        // Only run if case retention policy is enabled
        $enabled = config('app.case_retention_policy_enabled', false);
        if (!$enabled) {
            Log::info('EvaluateProcessRetentionJob: Case retention policy is disabled, skipping', [
                'process_id' => $this->processId,
            ]);

            return;
        }

        $process = Process::find($this->processId);
        if (!$process) {
            Log::error('EvaluateProcessRetentionJob: Process not found', [
                'process_id' => $this->processId,
            ]);

            return;
        }

        // Default to 1_year if retention_period is not set
        $retentionPeriod = $process->properties['retention_period'] ?? '1_year';
        $retentionMonths = match ($retentionPeriod) {
            '6_months' => 6,
            '1_year' => 12,
            '3_years' => 36,
            '5_years' => 60,
            default => 12, // Default to 1_year
        };

        Log::info('EvaluateProcessRetentionJob: Retention configuration loaded', [
            'process_id' => $this->processId,
            'process_name' => $process->name,
            'retention_period' => $retentionPeriod,
            'retention_months' => $retentionMonths,
        ]);

        // Default retention_updated_at to now if not set
        // This means the retention policy applies from now for processes without explicit retention settings
        $retentionUpdatedAt = isset($process->properties['retention_updated_at'])
            ? Carbon::parse($process->properties['retention_updated_at'])
            : Carbon::now();

        // Check if there are any process requests for this process
        if (!ProcessRequest::where('process_id', $this->processId)->exists()) {
            Log::info('EvaluateProcessRetentionJob: No process requests found, nothing to evaluate', [
                'process_id' => $this->processId,
            ]);

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

        Log::info('EvaluateProcessRetentionJob: Retention cutoff dates calculated', [
            'process_id' => $this->processId,
            'retention_updated_at' => $retentionUpdatedAt->toIso8601String(),
            'old_cases_cutoff' => $oldCasesCutoff->toIso8601String(),
            'new_cases_cutoff' => $newCasesCutoff->toIso8601String(),
            'current_time' => $now->toIso8601String(),
        ]);

        // Use subquery to get process request IDs
        $processRequestSubquery = ProcessRequest::where('process_id', $this->processId)->select('id');

        $totalDeleted = 0;
        $chunkCount = 0;
        $processId = $this->processId;

        CaseNumber::whereIn('process_request_id', $processRequestSubquery)
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
            ->chunkById(100, function ($cases) use (&$totalDeleted, &$chunkCount, $processId) {
                $caseIds = $cases->pluck('id');
                $chunkSize = $caseIds->count();

                // Delete the cases
                CaseNumber::whereIn('id', $caseIds)->delete();

                $totalDeleted += $chunkSize;
                $chunkCount++;

                Log::info('EvaluateProcessRetentionJob: Deleted chunk of cases', [
                    'process_id' => $processId,
                    'chunk_number' => $chunkCount,
                    'cases_deleted' => $chunkSize,
                ]);
            });

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('EvaluateProcessRetentionJob: Evaluation completed', [
            'process_id' => $this->processId,
            'total_cases_deleted' => $totalDeleted,
            'total_chunks_processed' => $chunkCount,
            'execution_time_ms' => $executionTime,
        ]);
    }
}
