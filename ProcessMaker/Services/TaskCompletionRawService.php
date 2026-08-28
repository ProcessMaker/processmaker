<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\Gate;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\TaskCompletionRawRepository;
use ProcessMaker\Support\TaskCompletionEngineBridge;
use ProcessMaker\SanitizeHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCompletionRawService
{
    public function __construct(
        private readonly TaskCompletionRawRepository $repository,
        private readonly TaskCompletionEngineBridge $engineBridge,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) config('app.task_update_v1_1_enabled', false);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function completeTask(int $taskId, array $payload, User $user): array
    {
        if (!$this->isEnabled()) {
            throw new NotFoundHttpException(
                __('Task update API v1.1 is disabled. Use PUT /api/1.0/tasks/{id} instead.')
            );
        }

        $taskRow = $this->repository->findTaskForUpdate($taskId);

        if ($taskRow === null) {
            throw new NotFoundHttpException(__('Task not found'));
        }

        if ($taskRow->status === 'CLOSED') {
            abort(422, __('Task already closed'));
        }

        $processRow = $this->repository->findProcessForComplete((int) $taskRow->process_id);

        if ($processRow === null) {
            throw new NotFoundHttpException(__('Process not found'));
        }

        Gate::forUser($user)->authorize(
            'update',
            $this->engineBridge->hydrateTokenForPolicy($taskRow, $processRow)
        );

        $requestRow = $this->repository->findProcessRequestForComplete((int) $taskRow->process_request_id);

        if ($requestRow === null) {
            throw new NotFoundHttpException(__('Process request not found'));
        }

        $versionRow = $this->repository->findProcessVersionForComplete(
            $requestRow->process_version_id ? (int) $requestRow->process_version_id : null
        );

        $data = SanitizeHelper::sanitizeData(
            $payload['data'] ?? [],
            null,
            $requestRow->do_not_sanitize ?? []
        );

        $this->engineBridge->complete(
            $taskRow,
            $processRow,
            $requestRow,
            $versionRow,
            $data,
            $this->repository->taskHasDraft($taskId),
        );

        $responseRow = $this->repository->findTaskForResponse($taskId);

        if ($responseRow === null) {
            throw new NotFoundHttpException(__('Task not found'));
        }

        return $this->formatTaskResponse($responseRow);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTaskResponse(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'element_name' => $row->element_name,
            'element_id' => $row->element_id,
            'element_type' => $row->element_type,
            'status' => $row->status,
            'due_at' => $row->due_at,
            'process_request_id' => (int) $row->process_request_id,
            'is_self_service' => (bool) $row->is_self_service,
            'token_properties' => $this->decodeJson($row->token_properties ?? null),
        ];
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
