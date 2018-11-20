<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\ProcessTaskAssignment;

class TaskAssignmentController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return ApiResource
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/task_assignments",
     *     summary="Save a new task assignments",
     *     operationId="createTaskAssignments",
     *     tags={"Task Assignments"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/taskAssignmentsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/taskAssignments")
     *     )
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
     * @param ProcessTaskAssignment $processTaskAssignment
     * @param Request $request
     *
     * @return ApiResource
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/task_assignments/{task_assignments_id}",
     *     summary="Update a task assignments",
     *     operationId="updateTaskAssignments",
     *     tags={"Task Assignments"},
     *     @OA\Parameter(
     *         description="ID of task assignment to return",
     *         in="path",
     *         name="task_assignments_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/taskAssignmentsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/taskAssignments")
     *     ),
     * )
     */
    public function update(ProcessTaskAssignment $processTaskAssignment, Request $request)
    {
        $request->validate(ProcessTaskAssignment::rules());
        $processTaskAssignment->fill($request->input());
        $processTaskAssignment->saveOrFail();
        return new ApiResource($processTaskAssignment->refresh());
    }
}
