<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Facades\ProcessMaker\RollbackProcessRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Events\ActivityReassignment;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\Task as Resource;
use ProcessMaker\Http\Resources\TaskCollection;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TaskReassignmentNotification;
use ProcessMaker\Query\SyntaxError;
use ProcessMaker\SanitizeHelper;
use ProcessMaker\Traits\TaskControllerIndexMethods;

class TaskController extends Controller
{
    use TaskControllerIndexMethods;

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'data',
        'pmql',
    ];

    private $statusMap = [
        'In Progress' => 'ACTIVE',
        'Completed' => 'CLOSED',
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param bool $getTotal used by Saved Search package to only return a total count instead of actual results
     * @param User $user used by Saved Search package to return accurate counts
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/tasks",
     *     summary="Returns all tasks that the user has access to",
     *     operationId="getTasks",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         description="Process request id",
     *         in="query",
     *         name="process_request_id",
     *         required=false,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of tasks",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/processRequestToken"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request, $getTotal = false, User $user = null)
    {
        // If a specific user is specified, use it; otherwise use the authorized user
        // This is necessary to produce accurate counts for Saved Searches
        if (!$user) {
            $user = Auth::user();
        }

        $query = $this->indexBaseQuery($request);

        $this->applyFilters($query, $request);

        $this->excludeNonVisibleTasks($query, $request);

        $this->applyColumnOrdering($query, $request);

        $this->applyStatusFilter($query, $request);

        $this->applyPmql($query, $request, $user);

        $this->applyAdvancedFilter($query, $request);

        $this->applyForCurrentUser($query, $user);

        // If only the total is being requested (by a Saved Search), send it now
        if ($getTotal === true) {
            return $query->count();
        }

        // Apply filter overdue
        $query->overdue($request->input('overdue'));

        // Apply pagination
        $query->paginate($request->input('per_page', 10));

        try {
            $response = $query->get();
        } catch (QueryException $e) {
            return $this->handleQueryException($e);
        }

        $response = $this->applyUserFilter($response, $request, $user);

        // Map each item through its resource
        $response = $this->applyResource($response);

        $inOverdueQuery = ProcessRequestToken::query()
            ->whereIn('id', $response->pluck('id'))
            ->where('due_at', '<', Carbon::now());

        $response->inOverdue = $inOverdueQuery->count();

        return new TaskCollection($response);
    }

    /**
     * Display the specified resource.
     * @TODO remove this method,view and route this is not a used file
     * @param ProcessRequestToken $task
     *
     * @return resource
     *
     * @OA\Get(
     *     path="/tasks/{task_id}",
     *     summary="Get a single task by ID",
     *     operationId="getTasksById",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         description="task id",
     *         in="path",
     *         name="task_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="include",
     *         in="query",
     *         name="include",
     *         required=false,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequestToken")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function show(ProcessRequestToken $task)
    {
        return new Resource($task);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ProcessRequestToken $task
     *
     * @return resource
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/tasks/{task_id}",
     *     summary="Update a task",
     *     operationId="updateTask",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         description="ID of task to update",
     *         in="path",
     *         name="task_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          required={"status","data"},
     *          @OA\Property(property="status", type="string", example="COMPLETED"),
     *          @OA\Property(property="data", type="object"),
     *       )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequestToken")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function update(Request $request, ProcessRequestToken $task)
    {
        $this->authorize('update', $task);
        if ($request->input('status') === 'COMPLETED') {
            if ($task->status === 'CLOSED') {
                return abort(422, __('Task already closed'));
            }
            // Skip ConvertEmptyStringsToNull and TrimStrings middlewares
            $data = json_decode($request->getContent(), true);
            $data = SanitizeHelper::sanitizeData($data['data'], null, $task->processRequest->do_not_sanitize ?? []);
            //Call the manager to trigger the start event
            $process = $task->process;
            $instance = $task->processRequest;
            WorkflowManager::completeTask($process, $instance, $task, $data);

            return new Resource($task->refresh());
        } elseif (!empty($request->input('user_id'))) {
            $userToAssign = $request->input('user_id');
            $task->reassign($userToAssign, $request->user());

            return new Resource($task->refresh());
        } else {
            return abort(422);
        }
    }

    private function handleQueryException($e)
    {
        $regex = '~Column not found: 1054 Unknown column \'(.*?)\' in \'where clause\'~';
        preg_match($regex, $e->getMessage(), $m);

        $message = __('PMQL Is Invalid.');

        if (count($m) > 1) {
            $message .= ' ' . __('Column not found: ') . '"' . $m[1] . '"';
        }

        \Log::error($e->getMessage());

        return response([
            'message' => $message,
        ], 422);
    }

    public function getScreen(Request $request, ProcessRequestToken $task, Screen $screen)
    {
        // Authorized in policy
        return new ApiResource($screen->versionFor($task->processRequest));
    }

    public function eligibleRollbackTask(Request $request, ProcessRequestToken $task)
    {
        $eligibleTask = RollbackProcessRequest::eligibleRollbackTask($task);
        if (!$eligibleTask) {
            return ['message' => __('Task can not be rolled back')];
        }

        return new Resource($eligibleTask);
    }

    public function rollbackTask(Request $request, ProcessRequestToken $task)
    {
        $processDefinitions = $task->process->getDefinitions();
        $newTask = RollbackProcessRequest::rollback($task, $processDefinitions);

        return new Resource($newTask);
    }

    public function setPriority(Request $request, ProcessRequestToken $task)
    {
        $task->update(['is_priority' => $request->input('is_priority', false)]);

        return new Resource($task->refresh());
    }

    /**
     * Only send data for a screen’s fields
     *
     * @param ProcessRequestToken $task
     *
     * @return array
     */
    public function getScreenFields(ProcessRequestToken $task)
    {
        $screenVersion = $task->getScreenVersion();
        if ($screenVersion) {
            return $screenVersion->screenFilteredFields();
        } else {
            return response()->json(['error' => 'Screen not found'], 404);
        }
    }
}
