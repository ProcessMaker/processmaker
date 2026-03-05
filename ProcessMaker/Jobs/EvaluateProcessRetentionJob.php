<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Http\Controllers\Api\Actions\Cases\DeletesCaseRecords;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;

class EvaluateProcessRetentionJob implements ShouldQueue
{
    use Queueable, DeletesCaseRecords;

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

        // Skip template processes
        if ($process->is_template) {
            Log::info('EvaluateProcessRetentionJob: Skipping template process', [
                'process_id' => $this->processId,
            ]);

            return;
        }

        // Skip processes in system categories
        if ($process->categories()->where('is_system', true)->exists()) {
            Log::info('EvaluateProcessRetentionJob: Skipping process in system category', [
                'process_id' => $this->processId,
            ]);

            return;
        }

        // Default to one_year if retention_period is not set
        $retentionPeriod = $process->properties['retention_period'] ?? 'one_year';
        $retentionMonths = match ($retentionPeriod) {
            'six_months' => 6,
            'one_year' => 12,
            'three_years' => 36,
            'five_years' => 60,
            default => 12, // Default to one_year
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

        // Collect all ProcessRequest IDs that will be deleted (to delete them after all chunks are processed)
        $processRequestIdsToDelete = [];
        $totalDeleted = 0;
        $chunkCount = 0;

        CaseNumber::whereIn('process_request_id', $processRequestSubquery)
            ->where($this->buildRetentionQuery($retentionUpdatedAt, $oldCasesCutoff, $newCasesCutoff))
            ->chunkById(100, function ($cases) use (&$processRequestIdsToDelete, &$totalDeleted, &$chunkCount) {
                $caseIds = $cases->pluck('id')->all();
                $processRequestIds = $cases->pluck('process_request_id')->unique()->all();

                // Collect ProcessRequest IDs for deletion after all chunks are processed
                $processRequestIdsToDelete = array_merge($processRequestIdsToDelete, $processRequestIds);

                $processRequestTokenIds = ProcessRequestToken::whereIn('process_request_id', $processRequestIds)->pluck('id')->all();
                $draftIds = $this->getTaskDraftIds($processRequestTokenIds);

                // uses case_number to delete
                $this->deleteCasesStarted($caseIds);
                $this->deleteCasesParticipated($caseIds);
                $this->deleteComments($caseIds, $processRequestIds, $processRequestTokenIds);

                // Delete the CaseNumber records that were returned by the query (by their IDs)
                CaseNumber::whereIn('id', $caseIds)->delete();

                $this->deleteProcessRequestLocks($processRequestIds, $processRequestTokenIds);
                $this->deleteInboxRuleLogs($processRequestTokenIds);
                $this->deleteInboxRules($processRequestTokenIds);
                $this->deleteProcessAbeRequestTokens($processRequestIds, $processRequestTokenIds);
                $this->deleteScheduledTasks($processRequestIds, $processRequestTokenIds);
                $this->deleteEllucianEthosSyncTasks($processRequestTokenIds);

                $this->deleteTaskDraftMedia($draftIds);
                $this->deleteTaskDrafts($processRequestTokenIds);
                

                $chunkCount++;
                $chunkSize = count($caseIds);
                $totalDeleted += $chunkSize;

                Log::info('EvaluateProcessRetentionJob: Deleted chunk of cases', [
                    'process_id' => $this->processId,
                    'chunk_number' => $chunkCount,
                    'cases_deleted' => $chunkSize,
                ]);
            });

        // Delete ProcessRequests after all chunks are processed
        // Only delete ProcessRequests that have no remaining cases
        if (!empty($processRequestIdsToDelete)) {
            $processRequestIdsToDelete = array_unique($processRequestIdsToDelete);

            // Filter to only ProcessRequests that have no remaining CaseNumbers
            $processRequestIdsWithNoCases = array_filter($processRequestIdsToDelete, function ($requestId) {
                return !CaseNumber::where('process_request_id', $requestId)->exists();
            });

            if (!empty($processRequestIdsWithNoCases)) {
                $this->deleteProcessRequests($processRequestIdsWithNoCases);

                // Delete any remaining related records
                $this->deleteRequestMedia($processRequestIdsWithNoCases);
                $this->deleteNotifications($processRequestIdsWithNoCases);

                $this->dispatchSavedSearchRecount();
            }
        }

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        Log::info('EvaluateProcessRetentionJob: Evaluation completed', [
            'process_id' => $this->processId,
            'total_cases_deleted' => $totalDeleted,
            'total_chunks_processed' => $chunkCount,
            'execution_time_ms' => $executionTime,
        ]);
    }

    /**
     * Build a retention query closure that can be applied to any query builder.
     *
     * This method encapsulates the retention evaluation logic:
     * - Cases created before retention_updated_at: delete if created before (retention_updated_at - retention_period)
     * - Cases created after retention_updated_at: delete if created before (now - retention_period)
     *
     * @param Carbon $retentionUpdatedAt The date when the retention policy was updated
     * @param Carbon $oldCasesCutoff The cutoff date for cases created before retention_updated_at
     * @param Carbon $newCasesCutoff The cutoff date for cases created after retention_updated_at
     * @return \Closure A closure that applies the retention query to a query builder
     */
    private function buildRetentionQuery(Carbon $retentionUpdatedAt, Carbon $oldCasesCutoff, Carbon $newCasesCutoff): \Closure
    {
        return function ($query) use ($retentionUpdatedAt, $oldCasesCutoff, $newCasesCutoff) {
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
        };
    }

    private function getTaskDraftIds(array $tokenIds): array
    {
        if ($tokenIds === []) {
            return [];
        }

        return TaskDraft::query()
            ->whereIn('task_id', $tokenIds)
            ->pluck('id')
            ->all();
    }
}
