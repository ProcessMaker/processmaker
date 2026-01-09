<?php

namespace ProcessMaker\Http\Controllers\Api\Actions\Cases;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\ProcessRequest;

class DeleteCase
{
    public function __invoke(string $caseNumber): void
    {
        // Delete later dependent records for requests and tokens (process_request_tokens,
        // process_request_locks, process_abe_request_tokens, scheduled_tasks,
        // inbox_rules, inbox_rule_logs, ellucian_ethos_sync_global_task_list, comments).

        $requestIds = ProcessRequest::query()
            ->where('case_number', $caseNumber)
            ->pluck('id')
            ->all();

        if ($requestIds === []) {
            abort(404);
        }

        DB::transaction(function () use ($caseNumber, $requestIds) {
            CaseStarted::query()
                ->where('case_number', $caseNumber)
                ->delete();

            CaseParticipated::query()
                ->where('case_number', $caseNumber)
                ->delete();

            CaseNumber::query()
                ->whereIn('process_request_id', $requestIds)
                ->delete();

            ProcessRequest::query()
                ->whereIn('id', $requestIds)
                ->delete();
        });
    }
}
