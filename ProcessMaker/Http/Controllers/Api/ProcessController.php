<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Process as Resource;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\Script;
use ProcessMaker\Jobs\ExportProcess;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Nayra\Bpmn\Models\TimerEventDefinition;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Nayra\Exceptions\ElementNotFoundException;
use ProcessMaker\Nayra\Storage\BpmnElement;
use ProcessMaker\Rules\BPMNValidation;

class ProcessController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'bpmn',
    ];

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
     *     tags={"Processes"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/status"),
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
     *                 ref="#/components/schemas/metadata",
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

        $processes = Process::nonSystem()->active()->with($include);
        if ($status === 'inactive') {
            $processes = Process::inactive()->with($include);
        }

        $processes = $processes->select('processes.*')
            ->leftJoin('process_categories as category', 'processes.process_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id')
            ->orderBy(...$orderBy)
            ->where($where)
            ->get();

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
     *     path="/processes/{processId}",
     *     summary="Get single process by ID",
     *     operationId="getProcessById",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/CreateNewProcess")
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
     *     tags={"Processes"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ProcessEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/CreateNewProcess")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Process::rules());
        $data = $request->all();
        //Validate if exists file bpmn
        if ($request->has('file')) {
            $data['bpmn'] = $request->file('file')->get();
            $request->request->add(['bpmn' => $data['bpmn']]);
            $request->request->remove('file');
            unset($data['file']);
        }

        if ($schemaErrors = $this->validateBpmn($request)) {
            return response(
                ['message' => __('The bpm definition is not valid'),
                    'errors' => ['bpmn' => $schemaErrors]],
                422
            );
        }

        $process = new Process();
        $process->fill($data);

        //set current user
        $process->user_id = Auth::user()->id;

        if (isset($data['bpmn'])) {
            $process->bpmn = $data['bpmn'];
        } else {
            $process->bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');
        }
        try {
            $process->saveOrFail();
        } catch (ElementNotFoundException $error) {
            return response(
                ['message' => __('The bpm definition is not valid'),
                    'errors' => [
                        'bpmn' => [
                            __('The bpm definition is not valid'),
                            __('Element ":element_id" not found', ['element_id' => $error->elementId])
                        ]
                    ]
                ],
                422
            );
        }
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
     *     path="/processes/{processId}",
     *     summary="Update a process",
     *     operationId="updateProcess",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
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
     *         @OA\JsonContent(ref="#/components/schemas/CreateNewProcess")
     *     ),
     * )
     */
    public function update(Request $request, Process $process)
    {
        $request->validate(Process::rules($process));

        //bpmn validation
        if ($schemaErrors = $this->validateBpmn($request)) {
            $warnings = [];
            foreach ($schemaErrors as $error) {
                if (is_string($error)) {
                    $text = str_replace('DOMDocument::schemaValidate(): ', '', $error);
                    $warnings[] = ['title' => __('Schema Validation'), 'text' => $text];
                } else {
                    $warnings[] = $error;
                }
            }
            $process->warnings = $warnings;
        } else {
            $process->warnings = null;
        }

        $process->fill($request->except('notifications', 'task_notifications', 'notification_settings', 'cancel_request', 'cancel_request_id', 'start_request_id', 'edit_data', 'edit_data_id'));

        // Catch errors to send more specific status
        try {
            $process->saveOrFail();
        } catch (TaskDoesNotHaveUsersException $e) {
            return response(
                ['message' => $e->getMessage(),
                    'errors' => ['bpmn' => $e->getMessage()]],
                422
            );
        }

        //If we are specifying cancel assignments...
        if ($request->has('cancel_request')) {
            $this->cancelRequestAssignment($process, $request);
        }

        //If we are specifying edit data assignments...
        if ($request->has('edit_data')) {
            $this->editDataAssignments($process, $request);
        }

        //Save any request notification settings...
        if ($request->has('notifications')) {
            $this->saveRequestNotifications($process, $request);
        }

        //Save any task notification settings...
        if ($request->has('task_notifications')) {
            $this->saveTaskNotifications($process, $request);
        }

        return new Resource($process->refresh());
    }

    private function cancelRequestAssignment(Process $process, Request $request)
    {
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

    private function editDataAssignments(Process $process, Request $request)
    {
        //Adding method to users array
        $editDataUsers = [];
        foreach ($request->input('edit_data')['users'] as $item) {
            $editDataUsers[$item] = ['method' => 'EDIT_DATA'];
        }

        //Adding method to groups array
        $editDataGroups = [];
        foreach ($request->input('edit_data')['groups'] as $item) {
            $editDataGroups[$item] = ['method' => 'EDIT_DATA'];
        }

        //Syncing users and groups that can cancel this process
        $process->usersCanEditData()->sync($editDataUsers, ['method' => 'EDIT_DATA']);
        $process->groupsCanEditData()->sync($editDataGroups, ['method' => 'EDIT_DATA']);
    }

    private function saveRequestNotifications($process, $request)
    {
        //Retrieve input
        $input = $request->input('notifications');

        //For each notifiable type...
        foreach ($process->requestNotifiableTypes as $notifiable) {
            //And for each notification type...
            foreach ($process->requestNotificationTypes as $notification) {
                //If this input has been set
                if (isset($input[$notifiable][$notification])) {
                    //Determine if this notification is wanted
                    $notificationWanted = filter_var($input[$notifiable][$notification], FILTER_VALIDATE_BOOLEAN);

                    //If we want the notification, find or create it
                    if ($notificationWanted === true) {
                        $process->notification_settings()->firstOrCreate([
                            'element_id' => null,
                            'notifiable_type' => $notifiable,
                            'notification_type' => $notification,
                        ]);
                    }

                    //If we do not want the notification, delete it
                    if ($notificationWanted === false) {
                        $process->notification_settings()
                            ->whereNull('element_id')
                            ->where('notifiable_type', $notifiable)
                            ->where('notification_type', $notification)
                            ->delete();
                    }
                }
            }
        }
    }

    /**
     * Validates the Bpmn content that comes in the request.
     * Returns the list of errors found
     *
     * @param Request $request
     * @return array|null
     */
    private function validateBpmn(Request $request)
    {
        $data = $request->all();
        $schemaErrors = null;
        if (isset($data['bpmn'])) {
            $document = new BpmnDocument();
            try {
                $document->loadXML($data['bpmn']);
            } catch (\ErrorException $e) {
                return [$e->getMessage()];
            }

            try {
                $validation = $document->validateBPMNSchema(public_path('definitions/ProcessMaker.xsd'));
            } catch (\Exception $e) {
                $schemaErrors = $document->getValidationErrors();
                $schemaErrors[] = $e->getMessage();
            }
            $schemaErrors = $this->validateOnlyOneDiagram($document, $schemaErrors);
            $rulesValidation = new BPMNValidation;
            if(!$rulesValidation->passes('document', $document)) {
                $errors = $rulesValidation->errors('document', $document)->getMessages();
                $schemaErrors[] = [
                    'title' => 'BPMN Validation failed',
                    'text' => 'Some bpmn elements do not comply with the validation',
                    'errors' => $errors,
                ];
            }
    }
        return $schemaErrors;
    }

    /**
     * Validate the bpmn has only one BPMNDiagram
     *
     * @param BpmnDocument $document
     * @param array $schemaErrors
     *
     * @return array
     */
    private function validateOnlyOneDiagram(BpmnDocument $document, array $schemaErrors = null)
    {
        $diagrams = $document->getElementsByTagNameNS('http://www.omg.org/spec/BPMN/20100524/DI', 'BPMNDiagram');
        if ($diagrams->length > 1) {
            $schemaErrors = $schemaErrors ?? [];
            $schemaErrors[] = __('Multiple diagrams are not supported');
        }
        return $schemaErrors;
    }

    private function saveTaskNotifications($process, $request)
    {
        //Retrieve input
        $inputs = $request->input('task_notifications');

        //For each node...
        foreach ($inputs as $nodeId => $input) {
            //For each notifiable type...
            foreach ($process->taskNotifiableTypes as $notifiable) {
                //And for each notification type...
                foreach ($process->taskNotificationTypes as $notification) {
                    //If this input has been set
                    if (isset($input[$notifiable][$notification])) {
                        //Determine if this notification is wanted
                        $notificationWanted = filter_var($input[$notifiable][$notification], FILTER_VALIDATE_BOOLEAN);

                        //If we want the notification, find or create it
                        if ($notificationWanted === true) {
                            $process->notification_settings()->firstOrCreate([
                                'element_id' => $nodeId,
                                'notifiable_type' => $notifiable,
                                'notification_type' => $notification,
                            ]);
                        }

                        //If we do not want the notification, delete it
                        if ($notificationWanted === false) {
                            $process->notification_settings()
                                ->where('element_id', $nodeId)
                                ->where('notifiable_type', $notifiable)
                                ->where('notification_type', $notification)
                                ->delete();
                        }
                    }
                }
            }
        }
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
     *     tags={"Processes"},
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
     *                 @OA\Items(ref="#/components/schemas/ProcessWithStartEvents"),
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
    public function startProcesses(Request $request)
    {
        $where = $this->getRequestFilterBy($request, ['processes.name', 'processes.description', 'category.name']);
        $orderColumns = explode(',', $request->input('order_by', 'name'));
        $orderDirections = explode(',', $request->input('order_direction', 'asc'));
        $include = $this->getRequestInclude($request);

        $query = Process::nonSystem()->with($include)->with('events')
            ->select('processes.*')
            ->leftJoin('process_categories as category', 'processes.process_category_id', '=', 'category.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id')
            ->where('processes.status', 'ACTIVE')
            ->where('category.status', 'ACTIVE')
            ->whereNull('warnings')
            ->where($where);

        // Add the order by columns
        foreach($orderColumns as $key=>$orderColumn) {
            $orderDirection = array_key_exists($key, $orderDirections) ? $orderDirections[$key] : 'asc';
            $query->orderBy($orderColumn, $orderDirection);
        }

        $processes = $query->get();

        foreach ($processes as $key => $process) {
            // filter the start events that can be used manually (no timer start events);
            // TODO: startEvents is not a real property on Process.
            // Move below to $process->getManualStartEvents();
            $process->startEvents = $process->events->filter(function ($event) {
                $eventIsTimerStart = collect($event['eventDefinitions'])
                        ->filter(function ($eventDefinition) {
                            return $eventDefinition['$type'] == 'timerEventDefinition';
                        })->count() > 0;
                return !$eventIsTimerStart;
            });

            if (count($process->startEvents) === 0) {
                $processes->forget($key);
            }
            // filter only valid executable processes
            if (!$process->isValidForExecution()) {
                $processes->forget($key);
            }
        }

        return new ApiCollection($processes); // TODO use existing resource class
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
     *     path="/processes/{processId}/restore",
     *     summary="Restore an inactive process",
     *     operationId="restoreProcess",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
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
        $process = Process::find($processId);
        $process->status = 'ACTIVE';
        $process->save();
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
     *     path="/processes/{processId}",
     *     summary="Delete a process",
     *     operationId="deleteProcess",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     * )
     */
    public function destroy(Process $process)
    {
        $process->status = 'INACTIVE';
        $process->save();

        return response('', 204);
    }

    /**
     * Export the specified process.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Post(
     *     path="/processes/{processId}/export",
     *     summary="Export a single process by ID",
     *     operationId="exportProcess",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
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
    public function export(Request $request, Process $process)
    {
        $fileKey = ExportProcess::dispatchNow($process);

        if ($fileKey) {
            $url = url("/processes/{$process->id}/download/{$fileKey}");
            return ['url' => $url];
        } else {
            return response(['error' => __('Unable to Export Process')], 500);
        }
    }

    /**
     * Import the specified process.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Post(
     *     path="/processes/import",
     *     summary="Import a new process",
     *     operationId="importProcess",
     *     tags={"Processes"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessImport")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     description="file to upload",
     *                     property="file",
     *                     type="file",
     *                     format="file",
     *                 ),
     *                 required={"file"}
     *             )
     *         )
     *     ),
     * )
     */
    public function import(Process $process, Request $request)
    {
        $content = $request->file('file')->get();
        if (!$this->validateImportedFile($content)) {
            return response(
                ['message' => __('Invalid Format')],
                422
            );
        }
        $import = ImportProcess::dispatchNow($content);
        return response([
            'status' => $import->status,
            'assignable' => $import->assignable,
            'process' => $import->process
        ]);
    }

    /**
     * Import Assignments of process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return Resource
     * @throws \Throwable
     *
     *
     * @OA\Post(
     *     path="/processes/{process_id}/import/assignments",
     *     summary="Update assignments after import",
     *     operationId="assignmentProcess",
     *     tags={"Processes"},
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
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProcessEditable")
     *     ),
     * )
     */
    public function importAssignments(Process $process, Request $request)
    {
        //If we are specifying assignments...
        if ($request->has('assignable')) {
            $assignable = $request->input('assignable');

            //Update assignments in scripts
            $xmlAssignable = [];
            $callActivity = [];
            foreach ($assignable as $assign) {
                if ($assign['type'] === 'script' && array_key_exists('value', $assign) && array_key_exists('id', $assign['value'])) {
                    Script::where('id', $assign['id'])
                        ->update(['run_as_user_id' => $assign['value']['id']]);
                } elseif ($assign['type'] === 'callActivity') {
                    $callActivity[] = $assign;
                } else {
                    $xmlAssignable[] = $assign;
                }
            }

            //Update assignments in start Events, task, user Tasks
            $definitions = $process->getDefinitions();
            $tags = ['startEvent', 'task', 'userTask', 'manualTask'];
            foreach ($tags as $tag) {
                $elements = $definitions->getElementsByTagName($tag);
                foreach ($elements as $element) {
                    $id = $element->getAttributeNode('id')->value;
                    foreach ($xmlAssignable as $assign) {
                        if ($assign['id'] == $id && array_key_exists('value', $assign) && array_key_exists('id', $assign['value'])) {
                            $value = $assign['value']['id'];
                            if (is_int($value)) {
                                $element->setAttribute('pm:assignment', 'user');
                                $element->setAttribute('pm:assignedUsers', $value);
                            } elseif (strpos($value, '-') !== false) {
                                $value = explode('-', $value);
                                $value = $value[1];
                                $element->setAttribute('pm:assignment', 'group');
                                $element->setAttribute('pm:assignmentGroup', 'group');
                                $element->setAttribute('pm:assignedGroups', $value);
                            } else {
                                $element->setAttribute('pm:assignment', $value);
                            }
                        }
                    }
                }
            }

            //Update assignments call Activity
            if ($callActivity) {
                $elements = $definitions->getElementsByTagName('callActivity');
                foreach ($elements as $element) {
                    $id = $element->getAttributeNode('id')->value;
                    foreach ($callActivity as $assign) {
                        if ($assign['id'] == $id && array_key_exists('value', $assign) && array_key_exists('id', $assign['value'])) {
                            $element->setAttribute('calledElement', $assign['value']['id']);
                        }
                    }
                }
            }

            $process->bpmn = $definitions->saveXML();
            $process->saveOrFail();
        }

        //If we are specifying cancel assignments...
        if ($request->has('cancel_request')) {
            $this->cancelRequestAssignment($process, $request);
        }

        //If we are specifying edit data assignments...
        if ($request->has('edit_data')) {
            $this->editDataAssignments($process, $request);
        }

        return response([
            'process' => $process->refresh()
        ], 204);
    }

    /**
     * Trigger an start event within a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return \ProcessMaker\Http\Resources\ProcessRequests
     *
     * @OA\Post(
     *     path="/process_events/{process_id}",
     *     summary="Start a new process",
     *     operationId="triggerStartEvent",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="process_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="Node ID of the start event",
     *         in="query",
     *         name="event",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *      @OA\RequestBody(
     *         description="data that will be stored as part of the created request",
     *         required=false,
     *         @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(
     *                     property="stringField",
     *                     type="string",
     *                     example="string example"
     *                 ),
     *                 @OA\Property(
     *                     property="integerField",
     *                     type="string",
     *                     example="1"
     *                 )
     *             )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequest")
     *     ),
     *
     * )
     */
    public function triggerStartEvent(Process $process, Request $request)
    {
        //Get the event BPMN element
        $id = $request->query('event');
        if (!$id) {
            return abort(404);
        }

        $definitions = $process->getDefinitions();
        if (!$definitions->findElementById($id)) {
            return abort(404);
        }
        $event = $definitions->getEvent($id);
        $data = request()->post();
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

    /**
     * Verify if the file is valid to be imported
     *
     * @param string $content
     *
     * @return bool
     */
    private function validateImportedFile($content)
    {
        $decoded = substr($content, 0, 1) === '{' ? json_decode($content) : (($content = base64_decode($content)) && substr($content, 0, 1) === '{' ? json_decode($content) : null);
        $isDecoded = $decoded && is_object($decoded);
        $hasType = $isDecoded && isset($decoded->type) && is_string($decoded->type);
        $validType = $hasType && $decoded->type === 'process_package';
        $hasVersion = $isDecoded && isset($decoded->version) && is_string($decoded->version);
        $validVersion = $hasVersion && method_exists(ImportProcess::class, "parseFileV{$decoded->version}");
        return $isDecoded && $validType && $validVersion;
    }
}
