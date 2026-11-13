<?php

declare(strict_types=1);

namespace ProcessMaker\Support;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Repositories\ProcessExecutionRawRepository;
use stdClass;

/**
 * Isolated bridge to the BPMN engine. Models are hydrated in memory from raw rows
 * without additional database queries.
 */
class TaskCompletionEngineBridge
{
    public function __construct(
        private readonly ProcessExecutionRawRepository $executionRawRepository,
    ) {
    }

    public function hydrateTokenForPolicy(stdClass $taskRow, stdClass $processRow): ProcessRequestToken
    {
        $process = $this->hydrateProcess($processRow);
        $task = $this->hydrateModel(
            ProcessRequestToken::class,
            $this->encodeArrayCasts((array) $taskRow, ['self_service_groups'])
        );
        $task->setRelation('process', $process);

        return $task;
    }

    public function complete(
        stdClass $taskRow,
        stdClass $processRow,
        stdClass $requestRow,
        ?stdClass $versionRow,
        array $data,
        bool $hasDraft,
    ): void {
        if ($hasDraft && TaskDraft::draftsEnabled()) {
            $task = $this->hydrateToken($taskRow, $requestRow, $processRow);
            TaskDraft::moveDraftFiles($task);
        }

        $process = $this->hydrateProcess($processRow);
        $processVersion = $versionRow ? $this->hydrateProcessVersion($versionRow, $processRow) : null;
        $instance = $this->hydrateProcessRequest($requestRow, $process, $processVersion);
        $task = $this->hydrateToken($taskRow, $requestRow, $processRow, $instance, $process);

        WorkflowManager::completeTask($process, $instance, $task, $data);
    }

    private function hydrateProcess(stdClass $row): Process
    {
        $attributes = (array) $row;
        $properties = is_array($attributes['properties'] ?? null)
            ? $attributes['properties']
            : [];

        if (!empty($row->manager_id)) {
            $properties['manager_id'] = $row->manager_id;
        }

        $attributes['properties'] = $properties;
        unset($attributes['manager_id']);

        return $this->hydrateModel(Process::class, $this->encodeArrayCasts($attributes, ['properties', 'start_events']));
    }

    private function hydrateProcessVersion(stdClass $row, stdClass $processRow): ProcessVersion
    {
        $process = $this->hydrateProcess($processRow);
        $version = $this->hydrateModel(
            ProcessVersion::class,
            $this->encodeArrayCasts((array) $row, ['start_events'])
        );
        $version->setRelation('process', $process);

        return $version;
    }

    private function hydrateProcessRequest(
        stdClass $row,
        Process $process,
        ?ProcessVersion $processVersion,
    ): ProcessRequest {
        $instance = $this->hydrateModel(
            ProcessRequest::class,
            $this->encodeArrayCasts((array) $row, ['do_not_sanitize'])
        );
        $instance->setRelation('process', $process);

        if ($processVersion !== null) {
            $instance->setRelation('processVersion', $processVersion);
        }

        return $instance;
    }

    private function hydrateToken(
        stdClass $taskRow,
        stdClass $requestRow,
        stdClass $processRow,
        ?ProcessRequest $instance = null,
        ?Process $process = null,
    ): ProcessRequestToken {
        $task = $this->hydrateModel(
            ProcessRequestToken::class,
            $this->encodeArrayCasts((array) $taskRow, ['self_service_groups'])
        );

        if ($instance === null || $process === null) {
            $process ??= $this->hydrateProcess($processRow);
            $instance ??= $this->hydrateProcessRequest($requestRow, $process, null);
        }

        $task->setRelation('processRequest', $instance);
        $task->setRelation('process', $process);

        return $task;
    }

    private function hydrateModel(string $class, array $attributes): mixed
    {
        return $this->executionRawRepository->hydrateModelFromRowRaw($class, (object) $attributes);
    }

    /**
     * Eloquent array casts expect JSON strings in raw attributes.
     *
     * @param list<string> $fields
     */
    private function encodeArrayCasts(array $attributes, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($attributes[$field]) && is_array($attributes[$field])) {
                $attributes[$field] = json_encode($attributes[$field]);
            }
        }

        return $attributes;
    }
}
