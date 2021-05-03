<?php
namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\Task as Resource;
use ProcessMaker\Http\Resources\TaskCollection;
use ProcessMaker\Query\SyntaxError;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Notifications\TaskReassignmentNotification;
use ProcessMaker\SanitizeHelper;
use Illuminate\Support\Str;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Models\User;
use ProcessMaker\Http\Resources\ApiResource;

class TaskController extends Controller
{
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
        if (! $user) {
            $user = Auth::user();
        }

        $query = ProcessRequestToken::with(['processRequest', 'user']);

        $query->select('process_request_tokens.*');

        $include  = $request->input('include') ? explode(',',$request->input('include')) : [];
        
        if (in_array('data', $include)) {
            unset($include[array_search('data', $include)]);
        }
        
        $query->with($include);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $query->filter($filter);
        }
        
        $filterByFields = ['process_id', 'process_request_tokens.user_id' => 'user_id', 'process_request_tokens.status' => 'status', 'element_id', 'element_name', 'process_request_id'];
        $parameters = $request->all();
        foreach ($parameters as $column => $fieldFilter) {
            if (in_array($column, $filterByFields)) {
                if ($column === 'user_id') {
                    $key = array_search($column, $filterByFields);
                    $query->where(function($query) use ($key, $column, $fieldFilter){
                        $userColumn = is_string($key) ? $key : $column;
                        $query->where($userColumn, $fieldFilter);
                        $query->orWhere(function ($query) use($userColumn, $fieldFilter) {
                            $query->whereNull($userColumn);
                            $query->where('process_request_tokens.is_self_service', 1);
                            $user = User::find($fieldFilter);
                            $query->where(function ($query) use ($user) {
                                foreach($user->groups as $group) {
                                    $query->orWhereJsonContains('process_request_tokens.self_service_groups', strval($group->getKey()));
                                }
                            });
                        });
                    });
                } elseif ($column === 'process_request_id') {
                    $key = array_search($column, $filterByFields);
                    $requestIdColumn = is_string($key) ? $key : $column;
                    if (empty($parameters['include_sub_tasks'])) {
                        $query->where($requestIdColumn, $fieldFilter);
                    } else {
                        // Include tasks from sub processes
                        $ids = ProcessRequest::find($fieldFilter)->childRequests()->pluck('id')->toArray();
                        $ids = Arr::prepend($ids, $fieldFilter);
                        $query->whereIn($requestIdColumn, $ids);
                    }
                } else {
                    $key = array_search($column, $filterByFields);
                    $query->where(is_string($key) ? $key : $column, 'like', $fieldFilter);
                }
            }
        }

        //list only display elements type task
        $query->where('element_type', '=', 'task');

        // order by one or more columns
        $orderColumns = explode(',', $request->input('order_by', 'updated_at'));
        foreach($orderColumns as $column) {
            if (!Str::contains($column, '.')) {
                $query->orderBy( $column, $request->input('order_direction', 'asc'));
            }
        }

        $statusFilter = $request->input('statusfilter', '');
        if ($statusFilter) {
            $statusFilter = array_map(function($value) {
                return mb_strtoupper(trim($value));
            }, explode(',', $statusFilter));
            $query->whereIn('status', $statusFilter);
        }

        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql, null, $user);
            } catch (QueryException $e) {
                return response(['message' => __('Your PMQL search could not be completed.')], 400);
            } catch (SyntaxError $e) {
                return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
            }
        }
        
        // If only the total is being requested (by a Saved Search), send it now
        if ($getTotal === true) {
            return $query->count();
        }
        
        $inOverdueQuery = ProcessRequestToken::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->where('due_at', '<', Carbon::now());

        $inOverdue = $inOverdueQuery->count();
        
        $response = $this->handleOrderByRequestName($request, $query->get());

        // Only filter results if the user id was specified
        if ($request->input('user_id') === $user->id) {
            $response = $response->filter(function($processRequestToken) use ($request, $user) {
                if ($request->input('status') === 'CLOSED') {
                    return $user->can('view', $processRequestToken->processRequest);
                }
                return $user->can('view', $processRequestToken);
            })->values();
        }
                
        //Map each item through its resource
        $response = $response->map(function ($processRequestToken) use ($request) {
            return new Resource($processRequestToken);
        });
        
        $response->inOverdue = $inOverdue;

        return new TaskCollection($response);
    }

    /**
     * Display the specified resource.
     * @TODO remove this method,view and route this is not a used file
     * @param ProcessRequestToken $task
     *
     * @return Resource
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
     * @return Resource
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
            $data = SanitizeHelper::sanitizeData($data['data'], $task->getScreen());
            //Call the manager to trigger the start event
            $process = $task->process;
            $instance = $task->processRequest;
            WorkflowManager::completeTask($process, $instance, $task, $data);
            return new Resource($task->refresh());
        } elseif (!empty($request->input('user_id'))) {
            $userToAssign = $request->input('user_id');
            if ($task->is_self_service && $userToAssign == Auth::id() && !$task->user_id) {
                // Claim task
                $task->is_self_service = 0;
                $task->user_id = $userToAssign;
                $task->persistUserData($userToAssign);
            } elseif ($userToAssign === '#manager') {
                // Reassign to manager
                $userToAssign = $task->escalateToManager();
                $task->persistUserData($userToAssign);
            } else {
                // Validate if user can reassign
                $task->authorizeReassignment(Auth::user());
                // Reassign user
                $task->reassignTo($userToAssign);
                $task->persistUserData($userToAssign);
            }
            $task->save();

            // Send a notification to the user
            $notification = new TaskReassignmentNotification($task);
            $task->user->notify($notification);
            event(new ActivityAssigned($task));
            return new Resource($task->refresh());
        } else {
            return abort(422);
        }
    }

    private function handleOrderByRequestName($request, $tasksList)
    {
        // Get the list of columns to order by - trimmed if spaces were added
        $orderColumns = collect(explode(',', $request->input('order_by', 'updated_at')))
            ->map(function($value, $key) {
                return trim($value);
            });
        $requestColumns = $orderColumns->filter(function($value, $key) {
            return Str::contains($value, 'process_requests.');
        })->sort();

        // if there ins't an order by request name, tasks are already ordered
        if ($requestColumns->count() == 0) {
            return $tasksList;
        }

        $requestQuery = DB::connection('data')->table('process_requests');

        foreach($requestColumns as $column) {
            $columnName = trim(explode('.', $column)[1]);
            $requestQuery->orderBy($columnName, $request->input('order_direction', 'asc'));
        }

        $orderedRequests = $requestQuery->get();
        $orderedTasks = collect([]);

        foreach($orderedRequests as $item) {
            $elements = $tasksList->filter(function ($value, $key) use($item) {
                return $value->process_request_id == $item->id;
            });

            $orderedTasks = $orderedTasks->merge($elements);
        }

        return $orderedTasks;
    }

    public function getScreen(Request $request, ProcessRequestToken $task, Screen $screen)
    {
        // Authorized in policy
        return new ApiResource($screen);
    }
}
