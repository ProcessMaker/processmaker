<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Http\Controllers\Api\Actions\Cases\DeletesCaseRecords;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;

class EvaluateProcessRetentionJob implements ShouldQueue
{
    use DeletesCaseRecords;
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
        $enabled = config('app.case_retention_policy_enabled', false);
        if (!$enabled) {
            return;
        }

        $process = Process::find($this->processId);
        if (!$process) {
            Log::error('CaseRetentionJob: Process not found', ['process_id' => $this->processId]);

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

        // Default retention_updated_at to now if not set
        // This means the retention policy applies from now for processes without explicit retention settings
        $retentionUpdatedAt = isset($process->properties['retention_updated_at'])
            ? Carbon::parse($process->properties['retention_updated_at'])
            : Carbon::now();

        // Check if there are any process requests for this process
        // If not, nothing to delete
        if (!ProcessRequest::where('process_id', $this->processId)->exists()) {
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

        // Use subquery to get process request IDs
        $processRequestSubquery = ProcessRequest::where('process_id', $this->processId)->select('id');

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
            ->chunkById(100, function ($cases) {
                $caseNumbers = $cases->pluck('id')->all();
                $requestIds = ProcessRequest::query()
                    ->whereIn('case_number', $caseNumbers)
                    ->pluck('id')
                    ->all();

                if ($requestIds === []) {
                    // If no ProcessRequest has case_number matching these CaseNumber ids, still remove case_numbers and UI entries
                    Log::warning('CaseRetentionJob: No process requests found for case numbers, removing case_numbers and cases_started/participated', [
                        'case_numbers' => $caseNumbers,
                        'process_id' => $this->processId,
                    ]);
                    DB::transaction(function () use ($caseNumbers) {
                        CaseNumber::whereIn('id', $caseNumbers)->delete();
                        foreach ($caseNumbers as $caseNumber) {
                            $this->deleteCasesStarted((string) $caseNumber);
                            $this->deleteCasesParticipated((string) $caseNumber);
                        }
                    });

                    return;
                }

                $tokenIds = ProcessRequestToken::query()
                    ->whereIn('process_request_id', $requestIds)
                    ->pluck('id')
                    ->all();
                $draftIds = $tokenIds !== []
                    ? TaskDraft::query()->whereIn('task_id', $tokenIds)->pluck('id')->all()
                    : [];

                DB::transaction(function () use ($requestIds, $tokenIds, $caseNumbers, $draftIds) {
                    $this->deleteInboxRuleLogs($tokenIds);
                    $this->deleteInboxRules($tokenIds);
                    $this->deleteProcessRequestLocks($requestIds, $tokenIds);
                    $this->deleteProcessAbeRequestTokens($requestIds, $tokenIds);
                    $this->deleteScheduledTasks($requestIds, $tokenIds);
                    $this->deleteEllucianEthosSyncTasks($tokenIds);
                    $this->deleteTaskDraftMedia($draftIds);
                    $this->deleteTaskDrafts($tokenIds);
                    foreach ($caseNumbers as $caseNumber) {
                        $this->deleteComments((string) $caseNumber, $requestIds, $tokenIds);
                    }
                    $this->deleteNotifications($requestIds);
                    $this->deleteRequestMedia($requestIds);
                    $this->deleteCaseNumbers($requestIds);
                    foreach ($caseNumbers as $caseNumber) {
                        $this->deleteCasesStarted((string) $caseNumber);
                        $this->deleteCasesParticipated((string) $caseNumber);
                    }
                    $this->deleteProcessRequestTokens($requestIds);
                    $this->deleteProcessRequests($requestIds);
                });
            });

        $this->dispatchSavedSearchRecount();
    }
}
