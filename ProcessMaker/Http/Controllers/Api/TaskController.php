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
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Notifications\TaskReassignmentNotification;
use ProcessMaker\SanitizeHelper;
use Illuminate\Support\Str;

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
    public function index(Request $request)
    {
        $query = ProcessRequestToken::with(['processRequest', 'user']);
//                ->leftJoin('process_requests', 'process_requests.id', '=', 'process_request_tokens.process_request_id');

        $query->select('process_request_tokens.*');

        $include  = $request->input('include') ? explode(',',$request->input('include')) : [];
        $query->with($include);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('element_name', 'like', $filter)
                    ->orWhere('process_request_tokens.status', 'like', $filter)
                    ->orWhere('request.name', 'like', $filter)
                    ->orWhere('user.firstname', 'like', $filter)
                    ->orWhere('user.lastname', 'like', $filter);
            });
        }
        $filterByFields = ['process_id', 'process_request_tokens.user_id' => 'user_id', 'process_request_tokens.status' => 'status', 'element_id', 'element_name', 'process_request_id'];
        $parameters = $request->all();
        foreach ($parameters as $column => $filter) {
            if (in_array($column, $filterByFields)) {
                $key = array_search($column, $filterByFields);
                $query->where(is_string($key) ? $key : $column, 'like', $filter);
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

        $inOverdueQuery = ProcessRequestToken::where('user_id', Auth::user()->id)
            ->where('status', 'ACTIVE')
            ->where('due_at', '<', Carbon::now());

        $inOverdue = $inOverdueQuery->count();

        $statusFilter = $request->input('statusfilter', '');
        if ($statusFilter) {
            $statusFilter = explode(',', $statusFilter);
            foreach ($statusFilter as $key => $status) {
                if ($key == 0) {
                    $query->where('process_request_tokens.status', trim(mb_strtoupper($status)));
                } else {
                    $query->orWhere('process_request_tokens.status', trim(mb_strtoupper($status)));
                }
            }
        }

        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql);
            } catch (QueryException $e) {
                return response(['message' => __('Your PMQL search could not be completed.')], 400);
            } catch (SyntaxError $e) {
                return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
            }
        }
        
        $response = $this->handleOrderByRequestName($request, $query->get());

        $response = $response->filter(function($processRequestToken) {
            return Auth::user()->can('view', $processRequestToken);
        })->values();

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
     *         required=false,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequestToken")
     *     ),
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
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/processRequestTokenEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequestToken")
     *     ),
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
            // Validate if user can reassign
            $task->authorizeReassignment(Auth::user());

            // Reassign user
            $task->user_id = $request->input('user_id');
            $task->save();

            // Send a notification to the user
            $notification = new TaskReassignmentNotification($task);
            $task->user->notify($notification);
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
}
