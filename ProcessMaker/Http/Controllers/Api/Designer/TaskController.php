<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\TaskManager;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\TaskTransformer;

class TaskController
{
    /**
     * Get a list of Tasks in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process, Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'title'),
            'sort_order' => $request->input('sort_order', 'ASC'),
        ];
        $response = TaskManager::index($process, $options);
        return fractal($response, new TaskTransformer())->respond();
    }

    /**
     * Get a single Task in a project.
     *
     * @param Process $process
     * @param Task $task
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Task $task)
    {
        $this->belongsToProcess($process, $task);
        return fractal($task, new TaskTransformer())->respond();
    }

    /**
     * Create a new Task in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'title' => $request->input('title', ''),
            'description' => $request->input('description', '')
        ];
        $data = array_merge($data, $this->formatData($request, ['type', 'assign_type', 'routing_type', 'priority_variable', 'assign_variable', 'group_variable', 'is_start_task', 'routing_screen_template', 'timing_control_configuration', 'self_service_trigger_id', 'self_service_timeout_configuration', 'custom_title', 'custom_description']));

        $response = TaskManager::save($process, $data);
        return fractal($response, new TaskTransformer())->respond(201);
    }

    /**
     * Update a Task in a project.
     *
     * @param Process $process
     * @param Task $task
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Task $task, Request $request)
    {
        $this->belongsToProcess($process, $task);
        $data = $this->formatData($request, ['title', 'description', 'type', 'assign_type', 'routing_type', 'priority_variable', 'assign_variable', 'group_variable', 'is_start_task', 'routing_screen_template', 'timing_control_configuration', 'self_service_trigger_id', 'self_service_timeout_configuration', 'custom_title', 'custom_description']);

        if ($data) {
            TaskManager::update($process, $task, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Task in a project.
     *
     * @param Process $process
     * @param Task $task
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Task $task)
    {
        $this->belongsToProcess($process, $task);
        TaskManager::remove($task);
        return response([], 204);
    }

    /**
     * Validate if Task belong to process.
     *
     * @param Process $process
     * @param Task $task
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Task $task): void
    {
        if ($process->id !== $task->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Task does not belong to this process.'));
        }
    }

    /**
     * Request data
     *
     * @param Request $request
     * @param array $fields
     *
     * @return array
     */
    private function formatData(Request $request, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }
        return $data;
    }

}
