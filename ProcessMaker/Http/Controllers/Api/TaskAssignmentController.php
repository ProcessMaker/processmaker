<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\TaskAssignmentResource;
use ProcessMaker\Models\ProcessTaskAssignment;

class TaskAssignmentController extends Controller
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
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/task_assignments",
     *     summary="Returns all task assignments",
     *     operationId="getTaskAssignments",
     *     tags={"Task Assignments"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of task assignments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/taskAssignments"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = ProcessTaskAssignment::select();
        $include = $request->input('include') ? explode(',', $request->input('include')) : [];
        $query->with($include);
        $filterByFields = ['process_id', 'process_task_id', 'assignment_id', 'assignment_type'];
        $parameters = $request->all();
        foreach ($parameters as $column => $filter) {
            if (in_array($column, $filterByFields)) {
                $key = array_search($column, $filterByFields);
                $query->where(is_string($key) ? $key : $column, 'like', $filter);
            }
        }
        $query->orderBy(
            $request->input('order_by', 'updated_at'), $request->input('order_direction', 'asc')
        );
        $response = $query->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Store a newly created task assignment in storage.
     *
     * @param  Request  $request
     * @return ApiResource
     *
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/task_assignments",
     *     summary="Save a new Task Assignment",
     *     operationId="createTaskAssignments",
     *     tags={"Task Assignments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/taskAssignmentsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/taskAssignments")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(ProcessTaskAssignment::rules());
        $assignment = new ProcessTaskAssignment();
        $assignment->fill($request->input());
        $assignment->saveOrFail();

        return new ApiResource($assignment->refresh());
    }

    /**
     * Update a task assignment
     *
     * @param  ProcessTaskAssignment  $task_assignment
     * @param  Request  $request
     * @return ApiResource
     *
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/task_assignments/{task_assignment}",
     *     summary="Update a Task Assignment",
     *     operationId="updateTaskAssignments",
     *     tags={"Task Assignments"},
     *     @OA\Parameter(
     *         description="ID of task assignment to update",
     *         in="path",
     *         name="task_assignment",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/taskAssignmentsEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function update(ProcessTaskAssignment $task_assignment, Request $request)
    {
        $request->validate(ProcessTaskAssignment::rules());
        $task_assignment->fill($request->input());
        $task_assignment->save();

        return new TaskAssignmentResource($task_assignment);
    }

    /**
     * Remove an assignment
     *
     * @param  ProcessTaskAssignment  $task_assignment
     * @return ResponseFactory|Response
     *
     * @OA\Delete(
     *     path="/task_assignments/{task_assignment}",
     *     summary="Delete a Task Assignment",
     *     operationId="deleteTaskAssignments",
     *     tags={"Task Assignments"},
     *     @OA\Parameter(
     *         description="ID of task assignment to delete",
     *         in="path",
     *         name="task_assignment",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/taskAssignmentsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *     ),
     * )
     */
    public function destroy(ProcessTaskAssignment $task_assignment)
    {
        $task_assignment->delete();

        return response('', 204);
    }
}
