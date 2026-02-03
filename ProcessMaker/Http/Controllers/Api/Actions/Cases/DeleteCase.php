<?php

namespace ProcessMaker\Http\Controllers\Api\Actions\Cases;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Events\CaseDeleted;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;

class DeleteCase
{
    use DeletesCaseRecords;

    public function __invoke(string $caseNumber): void
    {
        $requestIds = $this->getRequestIds($caseNumber);

        if ($requestIds === []) {
            abort(404);
        }

        $caseTitle = $this->getCaseTitle($caseNumber);
        $tokenIds = $this->getRequestTokenIds($requestIds);

        DB::transaction(function () use ($caseNumber, $requestIds, $tokenIds) {
            $this->deleteInboxRuleLogs($tokenIds);
            $this->deleteInboxRules($tokenIds);
            $this->deleteProcessRequestLocks($requestIds, $tokenIds);
            $this->deleteProcessAbeRequestTokens($requestIds, $tokenIds);
            $this->deleteScheduledTasks($requestIds, $tokenIds);
            $this->deleteEllucianEthosSyncTasks($tokenIds);
            $draftIds = $this->getTaskDraftIds($tokenIds);
            $this->deleteTaskDraftMedia($draftIds);
            $this->deleteTaskDrafts($tokenIds);
            $this->deleteComments($caseNumber, $requestIds, $tokenIds);
            $this->deleteNotifications($requestIds);
            $this->deleteRequestMedia($requestIds);
            $this->deleteCaseNumbers($requestIds);
            $this->deleteCasesStarted($caseNumber);
            $this->deleteCasesParticipated($caseNumber);
            $this->deleteProcessRequestTokens($requestIds);
            $this->deleteProcessRequests($requestIds);
        });

        CaseDeleted::dispatch($caseNumber, $caseTitle);

        $this->dispatchSavedSearchRecount();
    }

    private function getRequestIds(string $caseNumber): array
    {
        return ProcessRequest::query()
            ->where('case_number', $caseNumber)
            ->pluck('id')
            ->all();
    }

    private function getCaseTitle(string $caseNumber): string
    {
        $caseStarted = CaseStarted::query()
            ->where('case_number', $caseNumber)
            ->first();

        if ($caseStarted) {
            return $caseStarted->case_title ?? "Case #{$caseNumber}";
        } else {
            // If CaseStarted doesn't exist, get case title from the first ProcessRequest
            $firstRequest = ProcessRequest::query()
            ->where('case_number', $caseNumber)
            ->whereNull('parent_request_id')
            ->first();

            return $firstRequest?->case_title ?? "Case #{$caseNumber}";
        }
    }

    private function getRequestTokenIds(array $requestIds): array
    {
        if ($requestIds === []) {
            return [];
        }

        return ProcessRequestToken::query()
            ->whereIn('process_request_id', $requestIds)
            ->pluck('id')
            ->all();
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

    private function dispatchSavedSearchRecount(): void
    {
        if (!config('savedsearch.count', false)) {
            return;
        }

        $jobClass = 'ProcessMaker\\Package\\SavedSearch\\Jobs\\RecountAllSavedSearches';
        if (!class_exists($jobClass)) {
            return;
        }

        DB::afterCommit(static function () use ($jobClass): void {
            $jobClass::dispatch(['request', 'task']);
        });
    }
}
