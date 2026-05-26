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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Events\ActivityReassignment;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\Task as Resource;
use ProcessMaker\Http\Resources\TaskCollection;
use ProcessMaker\Jobs\CaseUpdate;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Models\UserResourceView;
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

    protected $defaultCase = [
        'id', // Task #
        'element_name', // Task Name
        'user_id', // Participant
        'process_id', // Process
        'completed_at', // Completed At
        'due_at', // Due At
        'process_request_id', // Request Id #
        'data',
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
     *     @OA\Parameter(
     *         description="Return all task types. Not just user tasks.",
     *         in="query",
     *         name="all_tasks",
     *         required=false,
     *         @OA\Schema(
     *           type="boolean",
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

        // Get fields from request (sent by frontend)
        // If not provided, don't apply select() to maintain backward compatibility (returns all columns)
        $fields = $request->input('fields', '');
        if ($fields) {
            $selectedFields = explode(',', $fields);
            // Ensure 'id' is always included for internal logic (e.g., inOverdueQuery at line ~186)
            if (!in_array('id', $selectedFields)) {
                $selectedFields[] = 'id';
            }
            $query = $query->select($selectedFields);
        }

        $this->applyFilters($query, $request);

        $this->excludeNonVisibleTasks($query, $request);

        $this->applyColumnOrdering($query, $request);

        $this->applyStatusFilter($query, $request);

        // Apply process manager filter BEFORE PMQL to avoid conflicts with is_self_service filtering
        if ($request->input('processesIManage') === 'true') {
            $this->applyProcessManager($query, $user, $request);
        } else {
            $this->applyForCurrentUser($query, $user);
        }

        $this->applyPmql($query, $request, $user);

        $this->applyAdvancedFilter($query, $request);

        // Apply filter overdue
        $query->overdue($request->input('overdue'));

        // If only the total is being requested (by a Saved Search), send it now
        if ($getTotal === true) {
            return $query->count();
        }

        try {
            $response = $query->paginate($request->input('per_page', 10));
        } catch (QueryException $e) {
            return $this->handleQueryException($e);
        }

        $response = $this->applyUserFilter($response, $request, $user);

        if ($response->total() > 0 && $request->input('processesIManage') === 'true') {
            // enable user manager in cache
            $this->enableUserManager($user);
        }

        $inOverdueQuery = ProcessRequestToken::query()
            ->whereIn('id', $response->pluck('id'))
            ->where('due_at', '<', Carbon::now());

        $response->inOverdue = $inOverdueQuery->count();

        return new TaskCollection($response);
    }

    /**
     * Get the task list related to the case
     * @param Request $request
     * @param User $user used by Saved Search package to return accurate counts
     * @return array
     */
    public function getTasksByCase(Request $request, User $user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        // Validate the inputs, including optional ones
        $request->validate([
            'case_number' => 'required|integer',
            'status' => 'nullable|string|in:ACTIVE,CLOSED',
            'order_by' => 'nullable|string|in:id,element_name,due_at,user.lastname,process.name',
            'order_direction' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer',
            'includeScreen' => 'sometimes|boolean',
        ]);

        $includeScreen = $request->input('includeScreen', false);

        // Get only the columns defined
        $query = ProcessRequestToken::select($this->defaultCase);
        // Filter by case_number
        $query->filterByCaseNumber($request);
        // Filter by status
        $query->filterByStatus($request);
        // Return the process information
        $query->getProcess();
        // Return the user information
        $query->getUser();
        // Filter only the task related to the user
        $this->applyForCurrentUser($query, $user);
        // Exclude non visible task
        $this->excludeNonVisibleTasks($query, $request);
        // Apply ordering only if a valid order_by field is provided
        $query->applyOrdering($request);

        try {
            $response = $query->applyPagination($request);

            if ($includeScreen) {
                $response = $this->addTaskData($response);
            }
            $response->inOverdue = 0;
        } catch (QueryException $e) {
            return $this->handleQueryException($e);
        }

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
            $data = json_optimize_decode($request->getContent(), true);
            $requestRow = $this->getProcessRequestRowForCompleteRaw($task->process_request_id);
            $data = SanitizeHelper::sanitizeData($data['data'], null, $this->getDoNotSanitizeFromRowRaw($requestRow));
            //Call the manager to trigger the start event
            $process = $this->getProcessForCompleteRaw($task->process_id);
            $instance = $this->getProcessRequestFromRowRaw($requestRow);
            $instance->setRelation('process', $process);
            if ($processVersion = $this->getProcessVersionForCompleteRaw($requestRow->process_version_id ?? null)) {
                $instance->setRelation('processVersion', $processVersion);
            }
            $task->setRelation('processRequest', $instance);
            $task->setRelation('process', $process);
            if ($this->taskHasDraftRaw($task->id)) {
                TaskDraft::moveDraftFiles($task);
            }
            WorkflowManager::completeTask($process, $instance, $task, $data);

            $responseProcess = $this->getProcessForResponseRaw($task->process_id);
            $responseInstance = $this->getProcessRequestForResponseRaw($task->process_request_id);
            $responseInstance->setRelation('process', $responseProcess);
            $taskRefreshed = $this->refreshTaskRaw($task, $responseProcess, $responseInstance);

            return new Resource($taskRefreshed);
        } elseif (!empty($request->input('user_id'))) {
            $process = $this->getProcessForReassignRaw($task->process_id);
            $task->setRelation('process', $process);

            $userToAssign = $request->input('user_id');
            $comments = $request->input('comments');
            $task->reassign($userToAssign, $request->user(), $comments);

            $taskRefreshed = $this->refreshTaskRaw($task, $process, $task->processRequest);

            CaseUpdate::dispatchSync($task->processRequest, $taskRefreshed);

            return new Resource($taskRefreshed);
        } else {
            return abort(422);
        }
    }

    /**
     * Analog to $task->processRequest->do_not_sanitize (Eloquent), from a raw request row.
     */
    private function getDoNotSanitizeFromRowRaw(object $requestRow): array
    {
        $doNotSanitize = $requestRow->do_not_sanitize ?? null;
        if ($doNotSanitize === null) {
            return [];
        }
        if (is_array($doNotSanitize)) {
            return $doNotSanitize;
        }

        return json_decode($doNotSanitize, true) ?? [];
    }

    /**
     * Analog to ProcessRequest::find / $task->processRequest for completeTask (without loading data JSON).
     */
    private function getProcessRequestRowForCompleteRaw(int $processRequestId): object
    {
        $requestRow = DB::selectOne(
            'SELECT do_not_sanitize, id, process_id, process_version_id, collaboration_uuid FROM process_requests WHERE id = ? LIMIT 1',
            [$processRequestId]
        );
        if (!$requestRow) {
            abort(404);
        }

        return $requestRow;
    }

    /**
     * Analog to $task->process for completeTask (BPMN only; required by getDefinition/validateData).
     */
    private function getProcessForCompleteRaw(int $processId): Process
    {
        $processRow = DB::selectOne(
            'SELECT id, bpmn FROM processes WHERE id = ? LIMIT 1',
            [$processId]
        );
        if (!$processRow) {
            abort(404);
        }

        return $this->hydrateModelFromRowRaw(Process::class, $processRow);
    }

    /**
     * Analog to $task->processRequest->processVersion when a version is pinned on the request.
     */
    private function getProcessVersionForCompleteRaw(?int $processVersionId): ?ProcessVersion
    {
        if (!$processVersionId) {
            return null;
        }

        $versionRow = DB::selectOne(
            'SELECT id, process_id, bpmn FROM process_versions WHERE id = ? LIMIT 1',
            [$processVersionId]
        );
        if (!$versionRow) {
            abort(404);
        }

        return $this->hydrateModelFromRowRaw(ProcessVersion::class, $versionRow);
    }

    /**
     * Analog to $task->processRequest after complete (without loading data JSON).
     */
    private function getProcessRequestForResponseRaw(int $processRequestId): ProcessRequest
    {
        $requestRow = DB::selectOne(
            'SELECT id, process_id, process_version_id, collaboration_uuid, do_not_sanitize, name, status, case_number, case_title, parent_request_id, user_id, uuid, process_collaboration_id, callable_id, initiated_at, completed_at, created_at, updated_at FROM process_requests WHERE id = ? LIMIT 1',
            [$processRequestId]
        );
        if (!$requestRow) {
            abort(404);
        }

        return $this->hydrateModelFromRowRaw(ProcessRequest::class, $requestRow);
    }

    /**
     * Analog to $task->process for the API response (without BPMN).
     */
    private function getProcessForResponseRaw(int $processId): Process
    {
        $processRow = DB::selectOne(
            'SELECT id, name FROM processes WHERE id = ? LIMIT 1',
            [$processId]
        );
        if (!$processRow) {
            abort(404);
        }

        return $this->hydrateModelFromRowRaw(Process::class, $processRow);
    }

    /**
     * Analog to $task->processRequest built from a raw row (without data JSON or BPMN).
     */
    private function getProcessRequestFromRowRaw(object $requestRow): ProcessRequest
    {
        return $this->hydrateModelFromRowRaw(ProcessRequest::class, $requestRow);
    }

    /**
     * Analog to $task->process / Process::find for reassign (without loading bpmn).
     */
    private function getProcessForReassignRaw(int $processId): Process
    {
        $processRow = DB::selectOne(
            'SELECT id, properties, stages, case_title FROM processes WHERE id = ? LIMIT 1',
            [$processId]
        );
        if (!$processRow) {
            abort(404);
        }

        return $this->hydrateModelFromRowRaw(Process::class, $processRow);
    }

    /**
     * Analog to $task->draft()->exists() (Eloquent).
     */
    private function taskHasDraftRaw(int $taskId): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 AS found FROM task_drafts WHERE task_id = ? LIMIT 1',
            [$taskId]
        );
    }

    /**
     * Analog to $task->refresh() (Eloquent), keeping preloaded relations for the response.
     */
    private function refreshTaskRaw(
        ProcessRequestToken $task,
        Process $process,
        ProcessRequest $instance
    ): ProcessRequestToken {
        $row = DB::selectOne(
            'SELECT * FROM process_request_tokens WHERE id = ? LIMIT 1',
            [$task->id]
        );
        if ($row) {
            $task->setRawAttributes((array) $row, true);
            $task->syncOriginal();
        }
        $task->setRelation('process', $process);
        $task->setRelation('processRequest', $instance);

        return $task;
    }

    /**
     * Hydrate an Eloquent model from a raw DB row (used by *Raw helpers above).
     */
    private function hydrateModelFromRowRaw(string $modelClass, object $row)
    {
        $model = new $modelClass();
        $model->setRawAttributes((array) $row, true);
        $model->exists = true;
        $model->syncOriginal();

        return $model;
    }

    public function updateReassign(Request $request)
    {
        $userToAssign = $request->input('user_id');
        if (is_array($request->process_request_token)) {
            foreach ($request->process_request_token as $value) {
                $processRequestToken = ProcessRequestToken::find($value);
                //Claim the task for the current user.
                $processRequestToken->reassign($request->user()->id, $request->user());

                //Reassign to the user.
                $processRequestToken->reassign($userToAssign, $request->user());
                $taskRefreshed = $processRequestToken->refresh();
                CaseUpdate::dispatchSync($processRequestToken->processRequest, $taskRefreshed);
            }
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

    public function setViewed(Request $request, ProcessRequestToken $task)
    {
        return UserResourceView::setViewed(Auth::user(), $task);
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
     * Only send data for a screen's fields
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

    public function userCanReassign(Request $request)
    {
        $taskIds = $request->input('tasks', '');
        $tasks = explode(',', $taskIds);

        if (empty($tasks)) {
            return response()->json(['message' => __('No tasks selected')], 422);
        }

        $response = [];
        foreach ($tasks as $taskId) {
            $task = ProcessRequestToken::findOrFail($taskId);
            $response[$taskId] = Gate::forUser($request->user())->allows('reassign', $task);
        }

        return response()->json($response);
    }
}
