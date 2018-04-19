<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use ProcessMaker\Exception\TaskAssignedException;
use ProcessMaker\Facades\TaskManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use Symfony\Component\HttpFoundation\Response;

class AssigneeController extends Controller
{
    /**
     * List the users and groups assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function getActivityAssignees(Process $process, Task $activity, Request $request)
    {
        try
        {
            $options = $this->verifyOptions($request);

            $response = TaskManager::loadAssignees($activity, $options);
            return response($response, 200);
        } catch (TaskAssignedException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * List the users and groups assigned to a task paged
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function getActivityAssigneesPaged(Process $process, Task $activity, Request $request)
    {
        try
        {
            $options = $this->verifyOptions($request);

            $response = TaskManager::loadAssignees($activity, $options, true);
            return response($response, 200);
        } catch (TaskAssignedException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Assign a user or group to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Task $activity, Request$request)
    {
        try
        {
            $options = [
                'aas_uid' => $request->input('aas_uid', ''),
                'aas_type' => $request->input('aas_type', ''),
            ];
            $response = TaskManager::saveAssignee($process, $activity, $options);
            return response('', 201);
        } catch (TaskAssignedException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Remove Assignee to Activity
     *
     * @param Process $process
     * @param Task $activity
     * @param string $assignee
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function remove(Process $process, Task $activity, $assignee)
    {
        try
        {
            $response = TaskManager::removeAssignee($process, $activity, $assignee);
            return response('', 200);
        } catch (TaskAssignedException $exception) {
            return response($exception->getMessage(), 400);
        }
    }

    /**
     * Get a single user or group assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param string $assignee
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getActivityAssignee(Process $process, Task $activity, $assignee)
    {
        try
        {
            $response = TaskManager::getInformationAssignee($process, $activity, $assignee);
            return response($response, 200);
        } catch (TaskAssignedException $exception) {
            return response($exception->getMessage(), 400);
        }
    }

    /**
     * Return a list of assignees of an activity
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getActivityAssigneesAll(Process $process, Task $activity, Request $request)
    {
        try
        {
            $options = $this->verifyOptions($request);

            $response = TaskManager::getInformationAllAssignee($process, $activity, $options);
            return response($response, 200);
        } catch (TaskAssignedException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Verify parameters in request
     *
     * @param Request $request
     * @return array
     */
    private function verifyOptions(Request $request): array
    {
        return [
            'filter' => $request->input('filter', ''),
            'start' => $request->input('start', 0),
            'limit' => $request->input('limit', 20),
            'type' => $request->input('type', 'All')
        ];
    }

}

