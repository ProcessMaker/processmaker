<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Task as Resource;
use ProcessMaker\Models\ProcessRequestToken;

class TaskController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $query = ProcessRequestToken::query();
        $include  = $request->input('include') ? explode(',',$request->input('include')) : [];
        $query->with($include);
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('element_name', 'like', $filter)
                    ->orWhere('status', 'like', $filter);
            });
        }
        $filterByFields = ['process_uuid', 'user_uuid', 'status', 'element_uuid', 'element_name'];
        $parameters = $request->all();
        foreach ($parameters as $column => $filter) {
            if (in_array($column, $filterByFields)) {
                $query->where($column, 'like', $filter);
            }
        }
        $query->orderBy(
            $request->input('order_by', 'updated_at'), $request->input('order_direction', 'asc')
        );
        $response = $query->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Display the specified resource.
     *
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
     */
    public function update(Request $request, ProcessRequestToken $task)
    {
        if ($request->input('status') === 'COMPLETED') {
            if ($task->status === 'CLOSED') {
                return abort(422, __('Task already closed'));
            }
            $data = $request->input();
            //Call the manager to trigger the start event
            $process = $task->process;
            $instance = $task->processRequest;
            WorkflowManager::completeTask($process, $instance, $task, $data);
            return new Resource($task->refresh());
        } else {
            return abort(422);
        }
    }
}
