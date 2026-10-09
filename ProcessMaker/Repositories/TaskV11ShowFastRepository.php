<?php

declare(strict_types=1);

namespace ProcessMaker\Repositories;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

class TaskV11ShowFastRepository
{
    private const USER_COLUMNS = [
        'id', 'uuid', 'email', 'firstname', 'lastname', 'username', 'status',
        'address', 'city', 'state', 'postal', 'country', 'phone', 'fax', 'cell',
        'title', 'birthdate', 'timezone', 'datetime_format', 'language', 'meta',
        'is_administrator', 'expires_at', 'loggedin_at', 'created_at', 'updated_at',
        'delegation_user_id', 'manager_id', 'schedule', 'avatar',
    ];

    private const REQUESTOR_COLUMNS = ['id', 'firstname', 'lastname', 'email'];

    public function findForShow(int $taskId): ProcessRequestToken
    {
        $token = ProcessRequestToken::query()->where('id', $taskId)->firstOrFail();

        $row = DB::selectOne(
            'SELECT id, uuid, process_id, user_id, parent_request_id, status, do_not_sanitize, errors,
                completed_at, initiated_at, created_at, updated_at, case_title, case_number, callable_id,
                process_version_id, name
            FROM process_requests
            WHERE id = ?
            LIMIT 1',
            [$token->process_request_id]
        );

        $processRequest = new ProcessRequest();
        $processRequest->setRawAttributes((array) $row, true);
        $processRequest->exists = true;
        $token->setRelation('processRequest', $processRequest);

        if ($token->user_id) {
            $user = User::query()->select(self::USER_COLUMNS)->find($token->user_id);
            $token->setRelation('user', $user);
        }

        if ($processRequest->user_id) {
            $requestor = User::query()->select(self::REQUESTOR_COLUMNS)->find($processRequest->user_id);
            $processRequest->setRelation('user', $requestor);
        }

        if ($token->process_id) {
            $process = Process::query()->select(['id', 'name'])->find($token->process_id);
            $token->setRelation('process', $process);
        }

        $token->load(['draft' => function ($query) {
            $query->select(['id', 'task_id', 'data']);
        }]);

        return $token;
    }
}
