<?php

namespace ProcessMaker\Http\Controllers\Api\Actions\Cases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\TaskDraft;

class DeleteCase
{
    public function __invoke(string $caseNumber): void
    {
        $requestIds = $this->getRequestIds($caseNumber);

        if ($requestIds === []) {
            abort(404);
        }

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
            $this->deleteRequestMedia($requestIds);
            $this->deleteCaseNumbers($requestIds);
            $this->deleteCasesStarted($caseNumber);
            $this->deleteCasesParticipated($caseNumber);
            $this->deleteProcessRequestTokens($requestIds);
            $this->deleteProcessRequests($requestIds);
        });
    }

    private function getRequestIds(string $caseNumber): array
    {
        return ProcessRequest::query()
            ->where('case_number', $caseNumber)
            ->pluck('id')
            ->all();
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

    private function deleteCasesStarted(string $caseNumber): void
    {
        CaseStarted::query()
            ->where('case_number', $caseNumber)
            ->delete();
    }

    private function deleteCasesParticipated(string $caseNumber): void
    {
        CaseParticipated::query()
            ->where('case_number', $caseNumber)
            ->delete();
    }

    private function deleteCaseNumbers(array $requestIds): void
    {
        if ($requestIds === []) {
            return;
        }

        CaseNumber::query()
            ->whereIn('process_request_id', $requestIds)
            ->delete();
    }

    private function deleteProcessRequests(array $requestIds): void
    {
        if ($requestIds === []) {
            return;
        }

        ProcessRequest::query()
            ->whereIn('id', $requestIds)
            ->delete();
    }

    private function deleteProcessRequestTokens(array $requestIds): void
    {
        if ($requestIds === []) {
            return;
        }

        ProcessRequestToken::query()
            ->whereIn('process_request_id', $requestIds)
            ->delete();
    }

    private function deleteProcessRequestLocks(array $requestIds, array $tokenIds): void
    {
        ProcessRequestLock::query()
            ->whereIn('process_request_id', $requestIds)
            ->delete();

        if ($tokenIds !== []) {
            ProcessRequestLock::query()
                ->whereIn('process_request_token_id', $tokenIds)
                ->delete();
        }
    }

    private function deleteProcessAbeRequestTokens(array $requestIds, array $tokenIds): void
    {
        ProcessAbeRequestToken::query()
            ->whereIn('process_request_id', $requestIds)
            ->delete();

        if ($tokenIds !== []) {
            ProcessAbeRequestToken::query()
                ->whereIn('process_request_token_id', $tokenIds)
                ->delete();
        }
    }

    private function deleteScheduledTasks(array $requestIds, array $tokenIds): void
    {
        ScheduledTask::query()
            ->whereIn('process_request_id', $requestIds)
            ->delete();

        if ($tokenIds !== []) {
            ScheduledTask::query()
                ->whereIn('process_request_token_id', $tokenIds)
                ->delete();
        }
    }

    private function deleteInboxRules(array $tokenIds): void
    {
        if ($tokenIds === []) {
            return;
        }

        InboxRule::query()
            ->whereIn('process_request_token_id', $tokenIds)
            ->delete();
    }

    private function deleteInboxRuleLogs(array $tokenIds): void
    {
        if ($tokenIds === []) {
            return;
        }

        InboxRuleLog::query()
            ->whereIn('process_request_token_id', $tokenIds)
            ->delete();
    }

    private function deleteEllucianEthosSyncTasks(array $tokenIds): void
    {
        if ($tokenIds === [] || !Schema::hasTable('ellucian_ethos_sync_global_task_list')) {
            return;
        }

        DB::table('ellucian_ethos_sync_global_task_list')
            ->whereIn('process_request_token_id', $tokenIds)
            ->delete();
    }

    private function deleteTaskDrafts(array $tokenIds): void
    {
        if ($tokenIds === []) {
            return;
        }

        TaskDraft::query()
            ->whereIn('task_id', $tokenIds)
            ->delete();
    }

    private function deleteTaskDraftMedia(array $draftIds): void
    {
        if ($draftIds === []) {
            return;
        }

        Media::query()
            ->where('model_type', TaskDraft::class)
            ->whereIn('model_id', $draftIds)
            ->get()
            ->each
            ->delete();
    }

    private function deleteRequestMedia(array $requestIds): void
    {
        if ($requestIds === []) {
            return;
        }

        Media::query()
            ->where('model_type', ProcessRequest::class)
            ->whereIn('model_id', $requestIds)
            ->get()
            ->each
            ->delete();
    }

    private function deleteComments(string $caseNumber, array $requestIds, array $tokenIds): void
    {
        Comment::query()
            ->where('case_number', $caseNumber)
            ->orWhere(function ($query) use ($requestIds, $tokenIds) {
                $query->where('commentable_type', ProcessRequest::class)
                    ->whereIn('commentable_id', $requestIds);

                if ($tokenIds !== []) {
                    $query->orWhere(function ($nestedQuery) use ($tokenIds) {
                        $nestedQuery->where('commentable_type', ProcessRequestToken::class)
                            ->whereIn('commentable_id', $tokenIds);
                    });
                }
            })
            ->delete();
    }
}
