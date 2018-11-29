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
    public $skipPermissionCheckFor = ['triggerStartEvent'];

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

        $processes = Process::with($include)
            ->select('processes.*')
            ->leftJoin('process_categories as category', 'processes.process_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id')
            ->where($where);

        //Verify what processes the current user can initiate, user Administrator can start everything.
        if (!Auth::user()->is_administrator) {
            $processId = Auth::user()->startProcesses();
            $processes->whereIn('processes.id', $processId);
        }
        $processes->orderBy(...$orderBy);

        return new ApiCollection($processes->paginate($perPage));
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

        ProcessPermission::where('process_id', $process->id)->delete();
        $cancelId = Permission::byGuardName('requests.cancel')->id;
        $startId = Permission::byGuardName('requests.create')->id;
        if ($request->has('cancel_request')) {
            foreach ($request->input('cancel_request')['users'] as $id) {
                $this->savePermission($process, User::class, $id, $cancelId);
            }

            foreach ($request->input('cancel_request')['groups'] as $id) {
                $this->savePermission($process, Group::class, $id, $cancelId);
            }
        }

        if ($request->has('start_request')) {
            foreach ($request->input('start_request')['users'] as $id) {
                $this->savePermission($process, User::class, $id, $startId);
            }

            foreach ($request->input('start_request')['groups'] as $id) {
                $this->savePermission($process, Group::class, $id, $startId);
            }
        }

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

        if (!\Auth::user()->hasProcessPermission($process, 'requests.create')) {
            throw new AuthorizationException("Not authorized to start this process");
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
