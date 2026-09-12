<?php

declare(strict_types=1);

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

class TokenPersistenceRawRepository
{
    /**
     * Persist token fields after persistActivityActivated (token must be fully prepared in memory).
     */
    public function saveActivatedToken(ProcessRequestToken $token): void
    {
        $this->updateToken($token, [
            'status',
            'element_id',
            'element_type',
            'element_name',
            'process_id',
            'process_request_id',
            'user_id',
            'is_self_service',
            'self_service_groups',
            'due_at',
            'initiated_at',
            'riskchanges_at',
            'token_properties',
            'stage_id',
            'stage_name',
        ]);
    }

    /**
     * Persist token fields after persistActivityCompleted.
     */
    public function saveCompletedToken(ProcessRequestToken $token): void
    {
        $this->updateToken($token, [
            'status',
            'element_id',
            'process_request_id',
            'completed_at',
            'token_properties',
        ]);
    }

    /**
     * Persist token fields after persistActivityClosed.
     */
    public function saveClosedToken(ProcessRequestToken $token): void
    {
        $this->updateToken($token, [
            'status',
            'element_id',
            'element_type',
            'element_name',
            'process_id',
            'process_request_id',
            'data',
            'token_properties',
        ]);
    }

    /**
     * Analog to ExecutionInstanceRepository::persistInstanceUpdated without Eloquent save.
     */
    public function persistInstanceUpdated(ProcessRequest $instance): void
    {
        $store = $instance->getDataStore();
        $row = DB::selectOne(
            'SELECT data, execution_revision FROM process_requests WHERE id = ? LIMIT 1',
            [$instance->getKey()]
        );

        if (!$instance->status) {
            $instance->status = 'ACTIVE';
        }

        $storedData = $row && $row->data ? json_decode((string) $row->data, true) : [];
        $mergedData = $store->updateArray(is_array($storedData) ? $storedData : []);
        $newRevision = (int) ($row->execution_revision ?? 0) + 1;

        $instance->data = $mergedData;
        $instance->execution_revision = $newRevision;

        $payload = [
            'data' => json_encode($mergedData),
            'execution_revision' => $newRevision,
            'updated_at' => Carbon::now(),
        ];

        foreach (['status', 'last_stage_id', 'last_stage_name', 'progress', 'completed_at'] as $field) {
            if (array_key_exists($field, $instance->getDirty())) {
                $payload[$field] = $instance->getAttributes()[$field];
            }
        }

        $this->runUpdate('process_requests', (int) $instance->getKey(), $payload);
        $instance->syncChanges();
    }

    /**
     * @param list<string> $fields
     */
    private function updateToken(ProcessRequestToken $token, array $fields): void
    {
        $payload = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $token->getAttributes())) {
                continue;
            }
            $payload[$field] = $this->serializeColumnValue($field, $token->getAttributes()[$field]);
        }

        $payload['updated_at'] = Carbon::now();

        $tokenId = (int) $token->getKey();
        if ($tokenId <= 0) {
            $token->saveOrFail();

            return;
        }

        $this->runUpdate('process_request_tokens', $tokenId, $payload);
        $token->syncChanges();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function runUpdate(string $table, int $id, array $payload): void
    {
        if ($payload === []) {
            return;
        }

        $columns = array_keys($payload);
        $assignments = implode(', ', array_map(static fn (string $column): string => "`{$column}` = ?", $columns));
        $values = array_values($payload);
        $values[] = $id;

        DB::update("UPDATE `{$table}` SET {$assignments} WHERE `id` = ?", $values);
    }

    private function serializeColumnValue(string $field, mixed $value): mixed
    {
        if (in_array($field, ['self_service_groups', 'token_properties', 'data'], true)) {
            return $value === null ? null : json_encode($value);
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
}
