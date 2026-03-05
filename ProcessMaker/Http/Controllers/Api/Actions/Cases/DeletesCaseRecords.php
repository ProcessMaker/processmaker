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
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\TaskDraft;

trait DeletesCaseRecords
{
    private function deleteCasesStarted(string | array $caseNumbers): void
    {
        if (is_array($caseNumbers) && $caseNumbers !== []) {
            CaseStarted::query()
                ->whereIn('case_number', $caseNumbers)
                ->delete();
        } else {
            CaseStarted::query()
                ->where('case_number', $caseNumbers)
                ->delete();
        }
    }

    private function deleteCasesParticipated(string | array $caseNumbers): void
    {
        if (is_array($caseNumbers)) {
            CaseParticipated::query()
                ->whereIn('case_number', $caseNumbers)
                ->delete();
        } else {
            CaseParticipated::query()
                ->where('case_number', $caseNumbers)
                ->delete();
        }
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
            ->get()
            ->each
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
            ->get()
            ->each
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

    private function deleteComments(string | array $caseNumbers, array $requestIds, array $tokenIds): void
    {
        if (is_array($caseNumbers) && $caseNumbers !== []) {
            $query = Comment::query()
                ->whereIn('case_number', $caseNumbers);
        } else {
            $query = Comment::query()
                ->where('case_number', $caseNumbers);
        }

        if ($requestIds !== [] || $tokenIds !== []) {
            $query->orWhere(function ($query) use ($requestIds, $tokenIds) {
                $query->where('commentable_type', ProcessRequest::class)
                    ->whereIn('commentable_id', $requestIds);

                if ($tokenIds !== []) {
                    $query->orWhere(function ($nestedQuery) use ($tokenIds) {
                        $nestedQuery->where('commentable_type', ProcessRequestToken::class)
                            ->whereIn('commentable_id', $tokenIds);
                    });
                }
            });
        }

        $query->delete();
    }

    private function deleteNotifications(array $requestIds): void
    {
        if ($requestIds === []) {
            return;
        }

        $notificationTypes = [
            'COMMENT',
            'FILE_SHARED',
            'TASK_CREATED',
            'TASK_COMPLETED',
            'TASK_REASSIGNED',
        ];

        Notification::query()
            ->whereIn('data->request_id', $requestIds)
            ->whereIn('data->type', $notificationTypes)
            ->delete();
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
