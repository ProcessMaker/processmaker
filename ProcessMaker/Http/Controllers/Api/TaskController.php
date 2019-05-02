<?php
namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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

class TaskController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        //
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
        $query = ProcessRequestToken
            ::join('process_requests as request', 'request.id', '=', 'process_request_tokens.process_request_id')
            ->join('users as user', 'user.id', '=', 'process_request_tokens.user_id')
            ->select('process_request_tokens.*');
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
        $filterByFields = ['process_id', 'user_id', 'process_request_tokens.status' => 'status', 'element_id', 'element_name', 'process_request_id'];
        $parameters = $request->all();
        foreach ($parameters as $column => $filter) {
            if (in_array($column, $filterByFields)) {
                $key = array_search($column, $filterByFields);
                $query->where(is_string($key) ? $key : $column, 'like', $filter);
            }
        }
        
        
        //list only display elements type task
        $query->where('element_type', '=', 'task');
        $query->orderBy(
            $request->input('order_by', 'updated_at'), $request->input('order_direction', 'asc')
        );

        $inOverdueQuery = ProcessRequestToken::where('user_id', Auth::user()->id)
            ->where('status', 'ACTIVE')
            ->where('due_at', '<', Carbon::now());

        $inOverdue = $inOverdueQuery->count();   

        $pmql = $request->input('pmql', '');    
        
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

        try {
            if (!empty($pmql)) {
                $query->pmql($pmql, function($expression) {
                    
                    //Handle request name
                    if ($expression->field->field() == 'request.name') {
                        return function($query) use($expression) {
                            $processRequests = ProcessRequest::where('name', $expression->value->value())->get();
                            $query->whereIn('process_request_tokens.process_request_id', $processRequests->pluck('id'));
                        };
                    }

                    //Handle task name
                    if ($expression->field->field() == 'name') {
                        return function($query) use($expression) {
                            $query->where('process_request_tokens.element_name', $expression->value->value());
                        };
                    }

                    //Handle task status
                    if ($expression->field->field() == 'status') {
                        return function($query) use($expression) {
                            $query->where('process_request_tokens.status', $expression->value->value());
                        };
                    }
                });
            }

            $response = $query->get();
            
        } catch (QueryException $e) {
            return response(['message' => __('Your PMQL search could not be completed.')], 400);
        } catch (SyntaxError $e) {
            return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
        }

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
            $data = $request->json('data');
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
}
