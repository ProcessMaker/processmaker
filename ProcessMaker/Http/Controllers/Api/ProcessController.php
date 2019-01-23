<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Process as Resource;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\User;

class ProcessController extends Controller
{
    /**
     * Get list Process
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * * @OA\Get(
     *     path="/processes",
     *     summary="Returns all processes that the user has access to",
     *     operationId="getProcesses",
     *     tags={"Process"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of processes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Process"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $where = $this->getRequestFilterBy($request, ['processes.name', 'processes.description', 'processes.status', 'category.name', 'user.firstname', 'user.lastname']);
        $orderBy = $this->getRequestSortBy($request, 'name');
        $perPage = $this->getPerPage($request);
        $include = $this->getRequestInclude($request);
        $status = $request->input('status');

        $processes = ($status === 'deleted')
                        ? Process::onlyTrashed()->with($include)
                        : Process::with($include);

        $processes = $processes->select('processes.*')
            ->leftJoin('process_categories as category', 'processes.process_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id')
            ->orderBy(...$orderBy)
            ->where($where)
            ->get();

        $processes = $processes->filter(function($process) {
            return Auth::user()->can('start', $process);
        })->values();

        return new ApiCollection($processes);
    }

    /**
     * Display the specified resource.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/processes/processId",
     *     summary="Get single process by ID",
     *     operationId="getProcessById",
     *     tags={"Process"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="process_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function show(Request $request, Process $process)
    {
        return new Resource($process);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     *
     * @OA\Post(
     *     path="/processes",
     *     summary="Save a new process",
     *     operationId="createProcess",
     *     tags={"Process"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ProcessEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Process::rules());
        $data = $request->json()->all();

        $process = new Process();
        $process->fill($data);

        //set current user
        $process->user_id = Auth::user()->id;

        if (isset($data['bpmn'])) {
            $process->bpmn = $data['bpmn'];
        } else {
            $process->bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');
        }
        $process->saveOrFail();
        return new Resource($process->refresh());
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param Process $process
     * @return ResponseFactory|Response
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/processes/processId",
     *     summary="Update a process",
     *     operationId="updateProcess",
     *     tags={"Process"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="process_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ProcessEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function update(Request $request, Process $process)
    {
        $request->validate(Process::rules($process));
        $original_attributes = $process->getAttributes();

        //bpmn validation
        libxml_use_internal_errors(true);
        $definitions = $process->getDefinitions();
        $res = $definitions->validateBPMNSchema(public_path('definitions/ProcessMaker.xsd'));
        if (!$res) {
            $schemaErrors = $definitions->getValidationErrors();
            return response(
                ['message' => 'The bpm definition is not valid',
                    'errors' => ['bpmn' => $schemaErrors]],
                422);
        }

        //$process->fill($request->except('cancelRequest', 'startRequest')->json()->all());
        $process->fill($request->except('cancel_request', 'start_request', 'cancel_request_id', 'start_request_id'));
        $process->saveOrFail();

        unset(
            $original_attributes['id'],
            $original_attributes['updated_at']
        );
        $process->versions()->create($original_attributes);

        //If we are specifying start assignments...
        if ($request->has('start_request')) {    
            //Adding method to users array
            $startUsers = [];
            foreach ($request->input('start_request')['users'] as $item) {
                $startUsers[$item] = ['method' => 'START'];
            }
            
            //Adding method to groups array
            $startGroups = [];
            foreach ($request->input('start_request')['groups'] as $item) {
                $startGroups[$item] = ['method' => 'START'];
            }

            //Syncing users and groups that can start this process
            $process->usersCanStart()->sync($startUsers, ['method' => 'START']);
            $process->groupsCanStart()->sync($startGroups, ['method' => 'START']);    
        }

        //If we are specifying cancel assignments...
        if ($request->has('cancel_request')) {
            //Adding method to users array
            $cancelUsers = [];
            foreach ($request->input('cancel_request')['users'] as $item) {
                $cancelUsers[$item] = ['method' => 'CANCEL'];
            }

            //Adding method to groups array            
            $cancelGroups = [];
            foreach ($request->input('cancel_request')['groups'] as $item) {
                $cancelGroups[$item] = ['method' => 'CANCEL'];
            }
            
            //Syncing users and groups that can cancel this process            
            $process->usersCanCancel()->sync($cancelUsers, ['method' => 'CANCEL']);
            $process->groupsCanCancel()->sync($cancelGroups, ['method' => 'CANCEL']);
        }
               

        return new Resource($process->refresh());
    }

    /**
     * Returns the list of processes that the user can start.
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * * @OA\Get(
     *     path="/start_processes",
     *     summary="Returns the list of processes that the user can start",
     *     operationId="startProcesses",
     *     tags={"Process"},
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of processes that the user can start",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Process"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function startProcesses(Request $request)
    {
        $orderBy = $this->getRequestSortBy($request, 'name');
        $perPage = $this->getPerPage($request);
        $include = $this->getRequestInclude($request);

        $processes = Process::with($include)
            ->select('processes.*')
            ->leftJoin('process_categories as category', 'processes.process_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id')
            ->where('processes.status', 'ACTIVE')
            ->where('category.status', 'ACTIVE')
            ->orderBy(...$orderBy)
            ->get();

        $processes = $processes->filter(function($process) {
            return Auth::user()->can('start', $process);
        });

        return new ApiCollection($processes);
    }

    /**
     * Reverses the soft delete of the element
     *
     * @param Request $request
     * @param Process $process
     * @return ResponseFactory|Response
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/processes/processId/restore",
     *     summary="Restore a soft deleted process",
     *     operationId="restoreProcess",
     *     tags={"Process"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="process_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ProcessEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function restore(Request $request, $processId)
    {
        $process = Process::withTrashed()->find($processId);
        $process->status='ACTIVE';
        $process->save();
        $process->restore();
        return new Resource($process->refresh());
    }

    private function savePermission($process, $assignableType, $assignableId, $permissionId)
    {
        $processPerm = new ProcessPermission();
        $processPerm->process_id = $process->id;
        $processPerm->permission_id = $permissionId;
        $processPerm->assignable_type = $assignableType;
        $processPerm->assignable_id = $assignableId;
        $processPerm->saveOrFail();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     * @throws \Illuminate\Validation\ValidationException
     *
     * @OA\Delete(
     *     path="/processes/processId",
     *     summary="Delete a process",
     *     operationId="deleteProcess",
     *     tags={"Process"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="process_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function destroy(Process $process)
    {
        $process->status='INACTIVE';
        $process->save();

        if ($process->collaborations->count() !== 0) {
            return response(
                ['message' => 'The item should not have associated collaboration',
                    'errors' => ['collaborations' => $process->collaborations->count()]],
                422);
        }

        if ($process->requests->count() !== 0) {
            return response(
                ['message' => 'The item should not have associated requests',
                    'errors' => ['requests' => $process->requests->count()]],
                422);
        }


        $process->delete();
        return response('', 204);
    }

    /**
     * Trigger an start event within a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return \ProcessMaker\Http\Resources\ProcessRequests
     */
    public function triggerStartEvent(Process $process, Request $request)
    {
        //Get the event BPMN element
        $id = $request->input('event');
        if (!$id) {
            return abort(404);
        }

        $definitions = $process->getDefinitions();
        if (!$definitions->findElementById($id)) {
            return abort(404);
        }
        $event = $definitions->getEvent($id);
        $data = request()->input();
        //Trigger the start event
        $processRequest = WorkflowManager::triggerStartEvent($process, $event, $data);
        return new ProcessRequests($processRequest);
    }


    /**
     * Get the where array to filter the resources.
     *
     * @param Request $request
     * @param array $searchableColumns
     *
     * @return array
     */
    protected function getRequestFilterBy(Request $request, array $searchableColumns)
    {
        $where = [];
        $filter = $request->input('filter');
        if ($filter) {
            foreach ($searchableColumns as $column) {
                // for other columns, it can match a substring
                $sub_search = '%';
                if (array_search('status', explode('.', $column), true) !== false) {
                    // filtering by status must match the entire string
                    $sub_search = '';
                }
                $where[] = [$column, 'like', $sub_search . $filter . $sub_search, 'or'];
            }
        }
        return $where;
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestSortBy(Request $request, $default)
    {
        $column = $request->input('order_by', $default);
        $direction = $request->input('order_direction', 'asc');
        return [$column, $direction];
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestInclude(Request $request)
    {
        $include = $request->input('include');
        return $include ? explode(',', $include) : [];
    }


    /**
     * Get the size of the page.
     * per_page=# (integer, the page requested) (Default: 10)
     *
     * @param Request $request
     * @return type
     */
    protected function getPerPage(Request $request)
    {
        return $request->input('per_page', 10);
    }

}
