<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Http\Request;
use ProcessMaker\Exception\TaskAssignedException;
use ProcessMaker\Facades\TaskManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Activity;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;

class AssigneeController extends Controller
{
    /**
     * List users and groups assigned to Task
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return array
     */
    public function getActivityAssignees(Process $process, Task $activity, Request $request)
    {
        //todo validate process and task
        $options = [
            'filter' => $request->input('filter', ''),
            'start' => $request->input('start', 0),
            'limit' => $request->input('limit', 20),
        ];
        return TaskManager::loadAssignees($activity, $options);
    }

    public function getActivityAssigneesPaged(Process $process, Activity $activity, Request $request)
    {

    }

    /**
     * Assign a user or group to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
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
            return response($exception->getMessage(), 400);
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
        //todo Validate process, task
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
     * @param $assignee
     *
     * @return TaskUser
     */
    /**
     * @param Process $process
     * @param Task $activity
     * @param $assignee
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

}
