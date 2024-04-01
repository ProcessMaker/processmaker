<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ProcessArchived;
use ProcessMaker\Events\ProcessCreated;
use ProcessMaker\Events\ProcessPublished;
use ProcessMaker\Events\ProcessRestored;
use ProcessMaker\Events\RequestAction;
use ProcessMaker\Events\RequestCreated;
use ProcessMaker\Events\TemplateUpdated;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Api\GroupController;
use ProcessMaker\Http\Controllers\Api\TemplateController;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\Process as Resource;
use ProcessMaker\Http\Resources\ProcessCollection;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Jobs\ExportProcess;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\Template;
use ProcessMaker\Nayra\Exceptions\ElementNotFoundException;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Package\WebEntry\Models\WebentryRoute;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Rules\BPMNValidation;
use Throwable;

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
        'svg',
    ];

    public $doNotSanitizeMustache = [
        'case_title',
    ];

    /**
     * Get list Process
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
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
        // Get the user
        $user = Auth::user();

        $orderBy = $this->getRequestSortBy($request, 'name');
        $perPage = $this->getPerPage($request);
        $include = $this->getRequestInclude($request);
        $status = $request->input('status');
        $pmql = $request->input('pmql', '');

        $processes = Process::nonSystem()->notArchived()->with($include);
        if ($status === 'archived') {
            $processes = Process::archived()->with($include);
        }
        if ($status === 'all') {
            $processes = Process::active()->with($include);
        }

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $processes->filter($filter);
        }
        // Filter by category
        $category = $request->input('category', null);
        if (!empty($category)) {
            $processes->processCategory($category);
        }

        // Filter by category status
        $processes->categoryStatus($request->input('cat_status', null));

        if (!empty($pmql)) {
            try {
                $processes->pmql($pmql);
            } catch (\ProcessMaker\Query\SyntaxError $e) {
                return response(['error' => 'PMQL error'], 400);
            }
        }

        // Get with bookmark
        $bookmark = $request->input('bookmark', false);

        $processes = $processes->with('events')
            ->select('processes.*')
            ->leftJoin(\DB::raw('(select id, uuid, name from process_categories) as category'), 'processes.process_category_id', '=', 'category.id')
            ->leftJoin(\DB::raw('(select id, uuid, username, lastname, firstname from users) as user'), 'processes.user_id', '=', 'user.id')
            ->orderBy(...$orderBy)
            ->get()
            ->collect();

        foreach ($processes as $key => $process) {
            // filter the start events that can be used manually (no timer start events);
            // TODO: startEvents is not a real property on Process.
            // Move below to $process->getManualStartEvents();
            $process->startEvents = $process->events->filter(function ($event) {
                $eventIsTimerStart = collect($event['eventDefinitions'])
                        ->filter(function ($eventDefinition) {
                            return $eventDefinition['$type'] == 'timerEventDefinition';
                        })->count() > 0;

                // Filter out web entry start events
                $eventIsWebEntry = false;
                if (isset($event['config'])) {
                    $config = json_decode($event['config'], true);
                    if (isset($config['web_entry']) && $config['web_entry'] !== null) {
                        $eventIsWebEntry = true;
                    }
                }

                return !$eventIsTimerStart && !$eventIsWebEntry;
            })->values();

            // Get the id bookmark related
            $process->bookmark_id = Bookmark::getBookmarked($bookmark, $process->id, $user->id);

            // Filter all processes that have event definitions (start events like message event, conditional event, signal event, timer event)
            if ($request->has('without_event_definitions') && $request->input('without_event_definitions') == 'true') {
                $startEvents = $process->events->filter(function ($event) {
                    return collect($event['eventDefinitions'])->isEmpty();
                });
            }

            // filter only valid executable processes
            if (!$process->isValidForExecution()) {
                $processes->startEvents = [];
            }
        }

        return new ProcessCollection($processes);
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
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/include"),
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
     * Display the specified resource.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/processes/{processId}/start_events",
     *     summary="Get start events of a process by Id",
     *     operationId="getStartEventsProcessById",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the start events process",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ProcessStartEvents"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),)
     *     ),
     * )
     */
    public function startEvents(Request $request, Process $process)
    {
        $startEvents = [];
        $currentUser = Auth::user();
        foreach ($process->start_events as $event) {
            if (count($event['eventDefinitions']) === 0) {
                if (array_key_exists('config', $event)) {
                    $webEntry = json_decode($event['config'])->web_entry;
                    $event['webEntry'] = $webEntry;
                }
                if (
                    $this->checkUserCanStartProcess($event, $currentUser->id, $process, $request) ||
                    Auth::user()->is_administrator
                ) {
                    $startEvents[] = $event;
                }
            }
        }

        return new ApiCollection($startEvents);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
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
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Process::rules());
        $data = $request->all();
        $processCreated = ProcessCreated::BLANK_CREATION;
        // If bpmn exists (from Generative AI)
        if ($request->input('bpmn')) {
            $data['bpmn'] = $request->input('bpmn');
        }

        // Validate if exists file bpmn
        if ($request->has('file')) {
            $data['bpmn'] = $request->file('file')->get();
            $request->merge(['bpmn' => $data['bpmn']]);
            $request->offsetUnset('file');
            unset($data['file']);
            $processCreated = ProcessCreated::BPMN_CREATION;
        }

        if ($schemaErrors = $this->validateBpmn($request)) {
            return response(
                ['message' => __('The bpm definition is not valid'),
                    'errors' => ['bpmn' => $schemaErrors], ],
                422
            );
        }

        $process = new Process();
        $process->fill($data);

        // set current user
        $process->user_id = Auth::user()->id;

        //set manager id
        if ($request->has('manager_id')) {
            $process->manager_id = $request->input('manager_id', null);
        }

        if (isset($data['bpmn'])) {
            $process->bpmn = $data['bpmn'];
        } else {
            $process->bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');
        }
        try {
            $process->saveOrFail();
            $process->syncProjectAsset($request, Process::class);
        } catch (ElementNotFoundException $error) {
            return response(
                ['message' => __('The bpm definition is not valid'),
                    'errors' => [
                        'bpmn' => [
                            __('The bpm definition is not valid'),
                            __('Element ":element_id" not found', ['element_id' => $error->elementId]),
                        ],
                    ],
                ],
                422
            );
        }
        // Register the Event
        ProcessCreated::dispatch($process->refresh(), $processCreated);

        return new Resource($process->refresh());
    }

    /**
     * Updates the current element.
     *
     * @param Request $request
     * @param Process $process
     * @return ResponseFactory|Response
     *
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
     *           type="integer",
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
        $rules = Process::rules($process);
        if (!$request->has('name')) {
            unset($rules['name']);
        }
        $request->validate($rules);
        $original = $process->getOriginal();

        // bpmn validation
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

        $process->fill($request->except('notifications', 'task_notifications', 'notification_settings', 'cancel_request', 'cancel_request_id', 'start_request_id', 'edit_data', 'edit_data_id', 'projects'));
        if ($request->has('manager_id')) {
            $process->manager_id = $request->input('manager_id', null);
        }

        // If we are specifying cancel assignments...
        if ($request->has('cancel_request')) {
            $this->cancelRequestAssignment($process, $request);
        }

        // If we are specifying edit data assignments...
        if ($request->has('edit_data')) {
            $this->editDataAssignments($process, $request);
        }

        // Save any request notification settings...
        if ($request->has('notifications')) {
            $this->saveRequestNotifications($process, $request);
        }

        // Save any task notification settings...
        if ($request->has('task_notifications')) {
            $this->saveTaskNotifications($process, $request);
        }

        $isTemplate = Process::select('is_template')->where('id', $process->id)->value('is_template');
        if ($isTemplate) {
            try {
                $response = (new TemplateController(new Template()))->updateTemplateManifest('process', $process->id, $request);

                //Call Event to Log Template Changes
                TemplateUpdated::dispatch([], [], true, $process);

                return new Resource($process->refresh());
            } catch (\Exception $error) {
                return ['error' => $error->getMessage()];
            }
        }

        $this->saveImagesIntoMedia($request, $process);
        // Catch errors to send more specific status
        try {
            $process->saveOrFail();
            $process->syncProjectAsset($request, Process::class);
        } catch (TaskDoesNotHaveUsersException $e) {
            return response(
                ['message' => $e->getMessage(),
                    'errors' => ['bpmn' => $e->getMessage()], ],
                422
            );
        }
        $changes = $process->getChanges();
        $changes['tmp_process_category_id'] = $request->input('process_category_id');

        // Register the Event
        ProcessPublished::dispatch($process->refresh(), $changes, $original);

        return new Resource($process->refresh());
    }

    public function updateBpmn(Request $request, Process $process)
    {
        $request->validate(Process::rules($process));

        // bpmn validation
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

        $process->bpmn = $request->input('bpmn');
        $process->name = $request->input('name');
        $process->description = $request->input('description');
        $process->saveOrFail();

        // If is a subprocess, we need to update the name in the BPMN too
        if ($request->input('parentProcessId') && $request->input('nodeId')) {
            $parentProcess = Process::findOrFail($request->input('parentProcessId'));
            $definitions = $parentProcess->getDefinitions();
            $elements = $definitions->getElementsByTagName('callActivity');
            foreach ($elements as $element) {
                if ($element->getAttributeNode('id')->value === $request->input('nodeId')) {
                    $element->setAttribute('name', $request->input('name'));
                }
            }
            $parentProcess->bpmn = $definitions->saveXML();
            $parentProcess->saveOrFail();
        }

        return response()->json([
            'success' => true,
        ], 200);
    }

    /**
     * Update draft process.
     *
     * @param Request $request
     * @param Process $process
     * @return ResponseFactory|Response
     *
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/processes/{processId}/draft",
     *     summary="Update a draft process",
     *     operationId="updateDraftProcess",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to return",
     *         in="path",
     *         name="processId",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
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
    public function updateDraft(Request $request, Process $process)
    {
        $request->validate(Process::rules($process));

        // BPMN validation.
        $schemaErrors = $this->validateBpmn($request);
        if ($schemaErrors) {
            $warnings = array_map(function ($error) {
                return is_string($error)
                    ? ['title' => __('Schema Validation'), 'text' => str_replace('DOMDocument::schemaValidate(): ', '', $error)]
                    : $error;
            }, $schemaErrors);
            $process->warnings = $warnings;
        } else {
            $process->warnings = null;
        }

        // Save any task notification settings...
        if ($request->has('task_notifications')) {
            $this->saveTaskNotifications($process, $request);
        }

        $process->fill($request->except('task_notifications'));

        try {
            $process->saveDraft();
        } catch (TaskDoesNotHaveUsersException $e) {
            return response(
                ['message' => $e->getMessage(),
                    'errors' => ['bpmn' => $e->getMessage()], ],
                422
            );
        }

        // Register the Event
        ProcessPublished::dispatch($process->refresh(), [], []);

        return new Resource($process->refresh());
    }

    private function cancelRequestAssignment(Process $process, Request $request)
    {
        $cancelRequest = $request->input('cancel_request');

        // Adding method to users array
        $cancelUsers = [];
        foreach ($cancelRequest['users'] as $item) {
            $cancelUsers[$item] = ['method' => 'CANCEL'];
        }

        // Adding method to groups array
        $cancelGroups = [];
        foreach ($cancelRequest['groups'] as $item) {
            $cancelGroups[$item] = ['method' => 'CANCEL'];
        }

        if (isset($cancelRequest['pseudousers'])) {
            if (in_array('manager', $cancelRequest['pseudousers'])) {
                $process->setProperty('manager_can_cancel_request', true);
            } else {
                $process->setProperty('manager_can_cancel_request', false);
            }
        }

        // Syncing users and groups that can cancel this process
        $process->usersCanCancel()->sync($cancelUsers, ['method' => 'CANCEL']);
        $process->groupsCanCancel()->sync($cancelGroups, ['method' => 'CANCEL']);
    }

    private function editDataAssignments(Process $process, Request $request)
    {
        // Adding method to users array
        $editDataUsers = [];
        foreach ($request->input('edit_data')['users'] as $item) {
            $editDataUsers[$item] = ['method' => 'EDIT_DATA'];
        }

        // Adding method to groups array
        $editDataGroups = [];
        foreach ($request->input('edit_data')['groups'] as $item) {
            $editDataGroups[$item] = ['method' => 'EDIT_DATA'];
        }

        // Syncing users and groups that can cancel this process
        $process->usersCanEditData()->sync($editDataUsers, ['method' => 'EDIT_DATA']);
        $process->groupsCanEditData()->sync($editDataGroups, ['method' => 'EDIT_DATA']);
    }

    private function saveRequestNotifications($process, $request)
    {
        // Retrieve input
        $input = $request->input('notifications');

        // For each notifiable type...
        foreach ($process->requestNotifiableTypes as $notifiable) {
            // And for each notification type...
            foreach ($process->requestNotificationTypes as $notification) {
                // If this input has been set
                if (isset($input[$notifiable][$notification])) {
                    // Determine if this notification is wanted
                    $notificationWanted = filter_var($input[$notifiable][$notification], FILTER_VALIDATE_BOOLEAN);

                    // If we want the notification, find or create it
                    if ($notificationWanted === true) {
                        $process->notification_settings()->firstOrCreate([
                            'element_id' => null,
                            'notifiable_type' => $notifiable,
                            'notification_type' => $notification,
                        ]);
                    }

                    // If we do not want the notification, delete it
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
     * Returns the list of errors found.
     *
     * @param Request $request
     */
    private function validateBpmn(Request $request): array
    {
        $data = $request->all();
        $schemaErrors = [];
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
            $rulesValidation = new BPMNValidation();
            if (!$rulesValidation->passes('document', $document)) {
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
     * Validate the bpmn has only one BPMNDiagram.
     */
    private function validateOnlyOneDiagram(BpmnDocument $document, array $schemaErrors = []): array
    {
        $diagrams = $document->getElementsByTagNameNS('http://www.omg.org/spec/BPMN/20100524/DI', 'BPMNDiagram');
        if ($diagrams->length > 1) {
            $schemaErrors[] = __('Multiple diagrams are not supported');
        }

        return $schemaErrors;
    }

    private function saveTaskNotifications($process, $request)
    {
        // Retrieve input
        $inputs = $request->input('task_notifications');

        // For each node...
        foreach ($inputs as $nodeId => $input) {
            // For each notifiable type...
            foreach ($process->taskNotifiableTypes as $notifiable) {
                // And for each notification type...
                foreach ($process->taskNotificationTypes as $notification) {
                    // If this input has been set
                    if (isset($input[$notifiable][$notification])) {
                        // Determine if this notification is wanted
                        $notificationWanted = filter_var($input[$notifiable][$notification], FILTER_VALIDATE_BOOLEAN);

                        // If we want the notification, find or create it
                        if ($notificationWanted === true) {
                            $process->notification_settings()->firstOrCreate([
                                'element_id' => $nodeId,
                                'notifiable_type' => $notifiable,
                                'notification_type' => $notification,
                            ]);
                        }

                        // If we do not want the notification, delete it
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
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *     @OA\Parameter(
     *         description="If true return only processes that haven't start event definitions",
     *         in="path",
     *         name="without_event_definitions",
     *         required=false,
     *         @OA\Schema(
     *           type="boolean",
     *         )
     *     ),
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
        foreach ($orderColumns as $key => $orderColumn) {
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

                // Filter out web entry start events
                $eventIsWebEntry = false;
                if (isset($event['config'])) {
                    $config = json_decode($event['config'], true);
                    if (isset($config['web_entry']) && $config['web_entry'] !== null) {
                        $eventIsWebEntry = true;
                    }
                }

                return !$eventIsTimerStart && !$eventIsWebEntry;
            })->values();

            // Filter all processes that have event definitions (start events like message event, conditional event, signal event, timer event)
            if ($request->has('without_event_definitions') && $request->input('without_event_definitions') == 'true') {
                $startEvents = $process->events->filter(function ($event) {
                    return collect($event['eventDefinitions'])->isEmpty();
                });

                if ($startEvents->isEmpty()) {
                    $processes->forget($key);
                }
            }

            if (count($process->startEvents) === 0) {
                $processes->forget($key);
            }
            // filter only valid executable processes
            if (!$process->isValidForExecution()) {
                $processes->forget($key);
            }
        }

        return new ApiCollection($processes->values()); // TODO use existing resource class
    }

    /**
     * Reverses the soft delete of the element.
     *
     * @param Request $request
     * @param Process $process
     * @return ResponseFactory|Response
     *
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
     *           type="integer",
     *         )
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
        // Register the Event
        ProcessRestored::dispatch($process->refresh());

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
     *
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
     *           type="integer",
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
        $process->status = 'ARCHIVED';
        $process->save();

        // Register the Event
        ProcessArchived::dispatch($process->refresh());

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
     *     summary="Export a single process by ID and return a URL to download it",
     *     operationId="exportProcess",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process to export",
     *         in="path",
     *         name="processId",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully built the process for export",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function export(Request $request, Process $process)
    {
        $fileKey = (new ExportProcess($process))->handle();

        if ($fileKey) {
            $url = url("/processes/{$process->id}/download/{$fileKey}");

            return ['url' => $url];
        } else {
            return response(['message' => __('Unable to Export Process')], 500);
        }
    }

    /**
     * Validate the specified process before importing.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Post(
     *     path="/processes/import/validation",
     *     summary="Validate a import",
     *     operationId="validateImport",
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
     *                     property="file",
     *                     description="file to import",
     *                     type="string",
     *                     format="binary",
     *                 ),
     *             )
     *         )
     *     ),
     * )
     */
    public function preimportValidation(Process $process, Request $request)
    {
        $content = $request->file('file')->get();
        $payload = json_decode($content);

        if (!$result = $this->validateImportedFile($content, $request)) {
            return response(
                ['message' => __('The selected file is invalid or not supported for the Process importer. Please verify that this file is a Process.')],
                422
            );
        }

        return $result;
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
     *                     property="file",
     *                     description="file to import",
     *                     type="string",
     *                     format="binary",
     *                 ),
     *             )
     *         )
     *     ),
     * )
     */
    public function import(Process $process, Request $request)
    {
        $content = $request->file('file')->get();
        if (!$this->validateImportedFile($content, $request)) {
            return response(
                ['message' => __('Invalid Format')],
                422
            );
        }
        $queue = $request->input('queue');
        if ($queue) {
            $path = $request->file('file')->store('imports');
            $code = uniqid('import', true);
            ImportProcess::dispatch(null, $code, $path, Auth::id());

            return [
                'code' => $code,
            ];
        }
        $import = (new ImportProcess($content))->handle();

        return response([
            'status' => $import->status,
            'assignable' => $import->assignable,
            'process' => $import->process,
            'processId' => $import->process->id,
        ]);
    }

    /**
     * Download the BPMN definition of a process
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/processes/{processId}/bpmn",
     *     summary="Download the BPMN definition of a process",
     *     operationId="processBpmn",
     *     tags={"Processes"},
     *     @OA\Parameter(
     *         description="ID of process",
     *         in="path",
     *         name="processId",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully built the process for export",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function downloadBpmn(Request $request, Process $process)
    {
        $bpmn = $process->bpmn;
        $filename = 'bpmnProcess.bpmn';

        return response()->streamDownload(function () use ($bpmn) {
            echo $bpmn;
        }, $filename);
    }

    /**
     * Check if the import is ready
     *
     * @param Request $request
     *
     * @OA\Head(
     *     path="/processes/import/{code}/is_ready",
     *     summary="Check if the import is ready",
     *     tags={"Processes"},
     *
     *     @OA\Parameter(
     *         description="Import code",
     *         in="path",
     *         name="code",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="check is import is ready",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="ready",
     *                 type="boolean",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function import_ready($code)
    {
        $user = Auth::user();
        $notifications = $user
            ->notifications()
            ->where('type', 'ProcessMaker\Notifications\ImportReady')
            ->get();
        foreach ($notifications as $notification) {
            if ($notification->data['code'] === $code) {
                $data = $notification->data['data'];
                $data['ready'] = true;

                return $data;
            }
        }

        return [
            'ready' => false,
        ];
    }

    /**
     * Import Assignments of process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return resource
     *
     * @throws \Throwable
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
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProcessAssignments"),
     *     ),
     * )
     */
    public function importAssignments(Process $process, Request $request)
    {
        // If we are specifying assignments...
        if ($request->has('assignable')) {
            $assignable = $request->input('assignable');

            // Update assignments in scripts
            $xmlAssignable = [];
            $callActivity = [];
            $watcherDataSources = [];
            foreach ($assignable as $assign) {
                if ($assign['type'] === 'script' && array_key_exists('value', $assign) && array_key_exists('id', $assign['value'])) {
                    $script = Script::where('id', $assign['id'])->firstOrFail();
                    $script->update(['run_as_user_id' => $assign['value']['id']]);
                } elseif ($assign['type'] === 'callActivity') {
                    $callActivity[] = $assign;
                } elseif ($assign['type'] === 'watcherDataSource') {
                    $watcherDataSources[] = $assign;
                } else {
                    $xmlAssignable[] = $assign;
                }
            }

            // Update assignments in start Events, task, user Tasks
            $definitions = $process->getDefinitions();
            $tags = ['startEvent', 'task', 'userTask', 'manualTask'];
            foreach ($tags as $tag) {
                $elements = $definitions->getElementsByTagName($tag);
                foreach ($elements as $element) {
                    $id = $element->getAttributeNode('id')->value;
                    foreach ($xmlAssignable as $assign) {
                        if ($assign['type'] === 'webentryCustomRoute' && $assign['id'] == $id) {
                            $this->checkForExistingRoute($process->id, $assign['value']);
                            $this->updateRoute($element, $assign['value']);
                        } elseif ($assign['id'] == $id && array_key_exists('value', $assign) && array_key_exists('id', $assign['value'])) {
                            $value = $assign['value']['id'];
                            if (is_int($value)) {
                                $element->setAttribute('pm:assignment', 'user_group');
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

            // Update assignments call Activity
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

            // Update data source watchers
            foreach ($watcherDataSources as $watcherDataSource) {
                $parts = explode('|', $watcherDataSource['id']);
                $screenId = $parts[0];
                $watcherIndex = intval($parts[1]);
                $screen = Screen::findOrFail($screenId);
                $watchers = $screen->watchers;
                $watchers[$watcherIndex]['script_id'] = $watcherDataSource['value']['id'];
                $watchers[$watcherIndex]['script'] = $watcherDataSource['value'];
                $watchers[$watcherIndex]['script']['id'] = 'data_source-' . strval($watcherDataSource['value']['id']);
                $watchers[$watcherIndex]['script']['title'] = $watcherDataSource['value']['name'];
                $screen->watchers = $watchers;
                $screen->saveOrFail();
            }

            $process->bpmn = $definitions->saveXML();
        }

        // If we are specifying cancel assignments...
        if ($request->has('cancel_request')) {
            $this->cancelRequestAssignment($process, $request);
        }

        // If we are specifying edit data assignments...
        if ($request->has('edit_data')) {
            $this->editDataAssignments($process, $request);
        }

        // If we are specifying a manager id
        if ($request->has('manager_id')) {
            $process->manager_id = $request->input('manager_id');
        }

        // If we are specifying a status
        if ($request->has('status')) {
            $process->status = $request->input('status');
        }

        $process->saveOrFail();

        // Register the Event
        ProcessCreated::dispatch($process->refresh(), ProcessCreated::BLANK_CREATION);

        return response([
            'process' => $process->refresh(),
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
     *           type="integer",
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
        // Get the event BPMN element
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
        // Validate if process is bpmn executable
        $validation = [];
        if (!$process->validateBpmnDefinition(false, $validation)) {
            return response()->json([
                'message' => $validation['title'] . ': ' . $validation['text'],
            ], 422);
        }
        // Trigger the start event
        try {
            $processRequest = WorkflowManager::triggerStartEvent($process, $event, $data);
        } catch (Throwable $exception) {
            throw $exception;

            return response()->json([
                'message' => __('Unable to start process'),
            ], 422);
        }

        event(new RequestAction($processRequest, RequestAction::ACTION_CREATED));

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
     * check if currentUser can start the request
     *
     * @param array $event
     * @param int $currentUser
     * @param Process $process
     * @param Request $request
     *
     * @return bool
     */
    protected function checkUserCanStartProcess($event, $currentUser, $process, $request)
    {
        $response = false;
        if (array_key_exists('assignment', $event)) {
            switch ($event['assignment']) {
                case 'user':
                    if (array_key_exists('assignedUsers', $event)) {
                        $response = $currentUser === (int) $event['assignedUsers'];
                    }
                    break;
                case 'group':
                    if (array_key_exists('assignedGroups', $event)) {
                        $response = $this->checkUsersGroup((int) $event['assignedGroups'], $request);
                    }
                    break;
                case 'process_manager':
                    $response = $currentUser === $process->manager_id;
                    break;
            }
        }

        return $response;
    }

    /**
     * check if currentUser is member of a group
     *
     * @param int $groupId
     * @param Request $request
     *
     * @return bool
     */
    protected function checkUsersGroup(int $groupId, Request $request)
    {
        $currentUser = Auth::user()->id;
        $group = Group::find($groupId);
        $response = false;
        if (isset($group)) {
            try {
                $responseUsers = (new GroupController(new Group()))->users($group, $request);
                $users = $responseUsers->all();

                foreach ($users as $user) {
                    if ($user->resource->member_id === $currentUser) {
                        $response = true;
                    }
                }
            } catch (\Exception $error) {
                return ['error' => $error->getMessage()];
            }

            try {
                $responseGroups = (new GroupController(new Group()))->groups($group, $request);
                $groups = $responseGroups->all();

                foreach ($groups as $group) {
                    if ($this->checkUsersGroup($group->resource->member_id, $request)) {
                        $response = true;
                    }
                }
            } catch (\Exception $error) {
                return ['error' => $error->getMessage()];
            }
        }

        return $response;
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
     * per_page=# (integer, the page requested) (Default: 10).
     *
     * @param Request $request
     * @return type
     */
    protected function getPerPage(Request $request)
    {
        return $request->input('per_page', 10);
    }

    /**
     * Verify if the file is valid to be imported.
     *
     * @param string $content
     *
     * @return bool
     */
    private function validateImportedFile($content, $request)
    {
        $decoded = substr($content, 0, 1) === '{' ? json_decode($content) : (($content = base64_decode($content)) && substr($content, 0, 1) === '{' ? json_decode($content) : null);
        $isDecoded = $decoded && is_object($decoded);
        $hasType = $isDecoded && isset($decoded->type) && is_string($decoded->type);
        $validType = $hasType && $decoded->type === 'process_package';
        $hasVersion = $isDecoded && isset($decoded->version) && is_string($decoded->version);
        $validVersion = $hasVersion && method_exists(ImportProcess::class, "parseFileV{$decoded->version}");
        $useNewImporter = $decoded !== null && property_exists($decoded, 'version') && (int) $decoded->version === 2;

        if ($validType && $useNewImporter) {
            return (new ImportController())->preview($request, $decoded->version);
        }

        return $isDecoded && $validType && $validVersion;
    }

    private function checkForExistingRoute($processId, $route)
    {
        $existingRoute = WebentryRoute::where('first_segment', $route)->where('process_id', '!=', $processId)->first();
        if ($existingRoute) {
            throw new \Exception('Segment should be unique. Used in process ' . $existingRoute->process_id . 'node ID: "' . $existingRoute->node_id . '"');
        }
    }

    private function updateRoute($node, $route)
    {
        $config = json_decode($node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config'), true);
        if ($config['web_entry']['webentryRouteConfig']['firstUrlSegment'] !== $route) {
            // update firstUrlSegment to new route
            $config['web_entry']['webentryRouteConfig']['firstUrlSegment'] = $route;

            // update entryUrl to new route
            $path = parse_url($config['web_entry']['webentryRouteConfig']['entryUrl'], PHP_URL_PATH);
            $newEntryUrl = str_replace($config['web_entry']['webentryRouteConfig']['firstUrlSegment'], $route, $path);
            $config['web_entry']['webentryRouteConfig']['entryUrl'] = $newEntryUrl;
            $node->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config', json_encode($config));
        }
    }

    public function close(Process $process)
    {
        $process->deleteDraft();

        return new Resource($process);
    }

    public function duplicate(Process $process, Request $request)
    {
        $request->validate(Process::rules());
        $newProcess = new Process();

        $exclude = ['id', 'uuid', 'created_at', 'updated_at'];
        foreach ($process->getAttributes() as $attribute => $value) {
            if (!in_array($attribute, $exclude)) {
                $newProcess->{$attribute} = $process->{$attribute};
            }
        }
        if ($request->has('name')) {
            $newProcess->name = $request->input('name');
        }
        if ($request->has('description')) {
            $newProcess->description = $request->input('description');
        }
        if ($request->has('process_category_id')) {
            $newProcess->process_category_id = $request->input('process_category_id');
        }
        $newProcess->saveOrFail();
        if ($request->has('projects')) {
            $newProcess->syncProjectAsset($request, Process::class);
        }

        return new ApiResource($newProcess);
    }

    public function saveImagesIntoMedia(Request $request, Process $process)
    {
        // Saving Carousel Images into Media table related to process_id
        if (is_array($request->imagesCarousel) && !empty($request->imagesCarousel)) {
            foreach ($request->imagesCarousel as $image) {
                if (is_string($image['url']) && !empty($image['url'])) {
                    if (!$process->media()->where('collection_name', 'images_carousel')
                        ->where('uuid', $image['uuid'])->exists()) {
                        $process->addMediaFromBase64($image['url'])->toMediaCollection('images_carousel');
                    }
                }
            }
        }
    }

    public function getMediaImages(Request $request, Process $process)
    {
        $media = Process::with(['media' => function ($query) {
            $query->orderBy('order_column', 'asc');
        }])
        ->where('id', $process->id)
        ->get();

        return new ProcessCollection($media);
    }

    public function deleteMediaImages(Request $request, Process $process)
    {
        $process = Process::find($process->id);

        // Get UUID image in media table
        $uuid = $request->input('uuid');

        $mediaImagen = $process->getMedia('images_carousel')
            ->where('uuid', $uuid)
            ->first();

        // Check if image exists before delete
        if ($mediaImagen) {
            $mediaImagen->delete();
        }
    }
}
