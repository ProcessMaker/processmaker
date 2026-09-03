<?php

declare(strict_types=1);

namespace ProcessMaker\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class TaskCompletionRawRepository
{
    public function __construct(
        private readonly ProcessExecutionRawRepository $executionRawRepository,
    ) {
    }

    public function findTaskForUpdate(int $taskId): ?stdClass
    {
        $row = DB::selectOne(
            'SELECT id, status, user_id, process_id, process_request_id, element_id, element_type,
                    element_name, is_self_service, self_service_groups
             FROM process_request_tokens
             WHERE id = ? AND element_type = ?
             LIMIT 1',
            [$taskId, 'task']
        );

        if ($row === null) {
            return null;
        }

        $row->is_self_service = (bool) $row->is_self_service;
        $row->self_service_groups = $this->decodeJson($row->self_service_groups);

        return $row;
    }

    public function findProcessForComplete(int $processId): ?stdClass
    {
        $row = DB::selectOne(
            'SELECT id, bpmn, start_events, properties, status, name, process_category_id
             FROM processes
             WHERE id = ? AND deleted_at IS NULL
             LIMIT 1',
            [$processId]
        );

        if ($row === null) {
            return null;
        }

        $row->properties = $this->decodeJson($row->properties) ?? [];
        $row->manager_id = $this->decodeManagerIds($row->properties['manager_id'] ?? null);
        $row->start_events = $this->decodeJson($row->start_events);

        return $row;
    }

    public function findProcessRequestForComplete(int $processRequestId): ?stdClass
    {
        $row = DB::selectOne(
            'SELECT id, process_id, process_version_id, status, do_not_sanitize, user_id,
                    parent_request_id, process_collaboration_id
             FROM process_requests
             WHERE id = ?
             LIMIT 1',
            [$processRequestId]
        );

        if ($row === null) {
            return null;
        }

        $row->do_not_sanitize = $this->decodeJson($row->do_not_sanitize) ?? [];

        return $row;
    }

    public function findProcessVersionForComplete(?int $processVersionId): ?stdClass
    {
        if ($processVersionId === null) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT id, process_id, bpmn, start_events, status, name, alternative
             FROM process_versions
             WHERE id = ?
             LIMIT 1',
            [$processVersionId]
        );

        if ($row === null) {
            return null;
        }

        $row->start_events = $this->decodeJson($row->start_events);

        return $row;
    }

    public function taskHasDraft(int $taskId): bool
    {
        return $this->executionRawRepository->taskHasDraftRaw($taskId);
    }

    public function findTaskForResponse(int $taskId): ?stdClass
    {
        return DB::selectOne(
            'SELECT id, element_name, element_id, element_type, status, due_at, process_request_id,
                    user_id, process_id, is_self_service, self_service_groups, token_properties,
                    created_at, updated_at, completed_at
             FROM process_request_tokens
             WHERE id = ?
             LIMIT 1',
            [$taskId]
        );
    }

    /**
     * @return list<int>
     */
    private function decodeManagerIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_map('intval', $value);
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        $decoded = $this->decodeJson($value);

        if (is_array($decoded)) {
            return array_map('intval', $decoded);
        }

        return [];
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
