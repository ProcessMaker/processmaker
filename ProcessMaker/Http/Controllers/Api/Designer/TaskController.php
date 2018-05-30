<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\TaskAssigneeManager;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\Process;
use ProcessMaker\TransTaskers\TaskTransTasker;

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
        $response = TaskAssigneeManager::index($process, $options);
        return fractal($response, new TaskTransTasker())->respond();
    }

    /**
     * Get a single Task in a project.
     *
     * @param Process $process
     * @param Task $Task
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Task $Task)
    {
        $this->belongsToProcess($process, $Task);
        return fractal($Task, new TaskTransTasker())->respond();
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
        $data = array_merge($data, $this->TaskatData($request, ['content']));

        if ($request->has('copy_import')) {
            $data['copy_import'] = $request->input('copy_import');
            return fractal(TaskAssigneeManager::copyImport($process, $data), new TaskTransTasker())->respond(201);
        }
        $response = TaskAssigneeManager::save($process, $data);
        return fractal($response, new TaskTransTasker())->respond(201);
    }

    /**
     * Update a Task in a project.
     *
     * @param Process $process
     * @param Task $Task
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Task $Task, Request $request)
    {
        $this->belongsToProcess($process, $Task);
        $data = $this->TaskatData($request, ['title', 'description', 'content']);

        if ($data) {
            TaskAssigneeManager::update($process, $Task, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Task in a project.
     *
     * @param Process $process
     * @param Task $Task
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Task $Task)
    {
        $this->belongsToProcess($process, $Task);
        TaskAssigneeManager::remove($Task);
        return response([], 204);
    }

    /**
     * Validate if Task belong to process.
     *
     * @param Process $process
     * @param Task $Task
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Task $Task): void
    {
        if ($process->id !== $Task->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Task does not belong to this process.'));
        }
    }

    /**
     * Taskat in capital letters to send inTaskation.
     *
     * @param Request $request
     * @param array $fields
     *
     * @return array
     */
    private function TaskatData(Request $request, array $fields): array
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
