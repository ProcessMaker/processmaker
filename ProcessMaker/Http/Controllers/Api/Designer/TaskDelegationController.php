<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\TasksDelegationManager;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Task;
use ProcessMaker\Transformers\TaskDelegationTransformer;

class TaskDelegationController
{
    /**
     * Get a list of Task Delegations in a project.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'title'),
            'sort_order' => $request->input('sort_order', 'ASC'),
        ];
        $response = TasksDelegationManager::index($options);
        return fractal($response, new TaskDelegationTransformer())
            ->parseIncludes(['user', 'application'])
            ->respond();
    }

    /**
     * Get a single task delegation in a project.
     *
     * @param Task $task
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Task $task)
    {
        $response = TasksDelegationManager::show($task);
        return fractal()
            ->item($response)
            ->parseIncludes(['user', 'application'])
            ->transformWith(new TaskDelegationTransformer())
            ->respond();
    }

}