<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
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
     * @throws DoesNotBelongToProcessException
     */
    public function getActivityAssignees(Process $process, Task $activity, Request $request)
    {
        $this->belongsToProcess($process, $activity);
        $options = $this->verifyOptions($request);
        $response = TaskManager::loadAssignees($activity, $options);

        return response($response, 200);
    }

    /**
     * List the users and groups assigned to a task paged
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function getActivityAssigneesPaged(Process $process, Task $activity, Request $request)
    {
        $this->belongsToProcess($process, $activity);
        $options = $this->verifyOptions($request);
        $response = TaskManager::loadAssignees($activity, $options, true);
        return response($response, 200);
    }

    /**
     * Assign a user or group to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function store(Process $process, Task $activity, Request$request)
    {
        $this->belongsToProcess($process, $activity);
        $options = [
            'aas_uid' => $request->input('aas_uid', ''),
            'aas_type' => $request->input('aas_type', ''),
        ];
        $response = TaskManager::saveAssignee($activity, $options);
        return response('', 201);
    }

    /**
     * Remove Assignee to Activity
     *
     * @param Process $process
     * @param Task $activity
     * @param string $assignee
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Task $activity, $assignee)
    {
        $this->belongsToProcess($process, $activity);
        TaskManager::removeAssignee($activity, $assignee);
        return response('', 200);
    }

    /**
     * Get a single user or group assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param string $assignee
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function getActivityAssignee(Process $process, Task $activity, $assignee)
    {
        $this->belongsToProcess($process, $activity);
        $response = TaskManager::getInformationAssignee($activity, $assignee);
        return response($response, 200);
    }

    /**
     * Get a list all the users who are assigned to a task (including users that are within groups).
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function getActivityAssigneesAll(Process $process, Task $activity, Request $request)
    {
        $this->belongsToProcess($process, $activity);
        $options = $this->verifyOptions($request);

        $response = TaskManager::getInformationAllAssignee($activity, $options);
        return response($response, 200);
    }

    /**
     * Get a list of available users and groups which may be assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function getActivityAvailable(Process $process, Task $activity, Request $request)
    {
        $this->belongsToProcess($process, $activity);
        $options = $this->verifyOptions($request);

        $response = TaskManager::loadAvailable($activity, $options);
        return response($response, 200);
    }

    /**
     * Get a page of the available users and groups which may be assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function getActivityAvailablePaged(Process $process, Task $activity, Request $request)
    {
        $this->belongsToProcess($process, $activity);
        $options = $this->verifyOptions($request);

        $response = TaskManager::loadAvailable($activity, $options, true);
        return response($response, 200);
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

    /**
    * Validate if Activity belong to process.
    *
    * @param Process $process
    * @param Task $activity
    *
    * @throws DoesNotBelongToProcessException|void
    */
    private function belongsToProcess(Process $process, Task $activity): void
    {
        if ($process->PRO_ID !== $activity->PRO_ID) {
            Throw new DoesNotBelongToProcessException(__('The Activity does not belong to this process.'));
        }
    }

}

