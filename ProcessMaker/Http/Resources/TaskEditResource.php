<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskEditResource extends JsonResource
{
    public function toArray($request)
    {
        // First decode the task if it's a JSON string
        $task = is_string($this->resource) ? json_decode($this->resource) : $this->resource;

        return [
            'id' => $task->id,
            'advanceStatus' => $task->advanceStatus ?? null,
            'process_request_id' => $task->process_request_id ?? null,
            'user' => $task->user ? [
                'id' => $task->user->id ?? null,
                'avatar' => $task->user->avatar ?? null,
            ] : null,

            'requestor' => $task->requestor ? [
                'id' => $task->requestor->id ?? null,
                'avatar' => $task->requestor->avatar ?? null,
            ] : null,

            'processRequest' => $task->processRequest ? [
                'id' => $task->processRequest->id ?? null,
                'status' => $task->processRequest->status ?? null,
                'case_number' => $task->processRequest->case_number ?? null,
                'name' => $task->processRequest->name ?? null,
            ] : null,

            'process' => $task->process ? [
                'id' => $task->process->id ?? null,
                'name' => $task->process->name ?? null,
                'manager_id' => $task->process->manager_id ?? null,
            ] : null,
            'loopContext' => $task->getLoopContext() ?? null,
            'screenId' => $task->screen['id'] ?? null,
            'process_request' => $task->processRequest ? [
                'id' => $task->processRequest->id ?? null,
                'name' => $task->processRequest->name ?? null,
                'status' => $task->processRequest->status ?? null,
            ] : null,
        ];
    }
}
