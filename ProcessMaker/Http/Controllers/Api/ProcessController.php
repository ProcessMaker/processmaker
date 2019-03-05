<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
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
use ProcessMaker\Jobs\ExportProcess;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Nayra\Bpmn\Models\TimerEventDefinition;

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
     *     tags={"Process"},
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

        $processes = ($status === 'inactive')
                        ? Process::inactive()->with($include)
                        : Process::active()->with($include);

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

        if (! isset($data['status'])) {
            $data['status'] = 'ACTIVE';
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

        $process->fill($request->except('notifications', 'task_notifications', 'notification_settings','cancel_request', 'cancel_request_id', 'start_request_id', 'edit_data', 'edit_data_id'));

        // Catch errors to send more specific status
        try {
            $process->saveOrFail();
        }
        catch (TaskDoesNotHaveUsersException $e) {
            return response(
                ['message' => $e->getMessage(),
                    'errors' => ['bpmn' => $e->getMessage()]],
                422);
        }

        unset(
            $original_attributes['id'],
            $original_attributes['updated_at']
        );
        $process->versions()->create($original_attributes);

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

        //If we are specifying cancel assignments...
        if ($request->has('edit_data')) {
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


        foreach($processes as $key => $process) {
            //filter he start events that can be used manually (no timer start events);
            $process->startEvents = $process->events->filter(function($event) {
                $eventIsTimerStart = collect($event['eventDefinitions'])
                                        ->filter(function($eventDefinition){
                                            return get_class($eventDefinition) == TimerEventDefinition::class;
                                        })->count() > 0;
                return !$eventIsTimerStart;
            });

            if (count($process->startEvents) === 0) {
                $processes->forget($key);
            }
        };

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
     *     summary="Restore an inactive process",
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
        $process = Process::find($processId);
        $process->status='ACTIVE';
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

        return response('', 204);
    }

    /**
     * Export the specified process.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/processes/processId/export",
     *     summary="Export a single process by ID",
     *     operationId="exportProcess",
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
    public function export(Request $request, Process $process)
    {
        $fileKey = ExportProcess::dispatchNow($process);

        if ($fileKey) {
            $url = url("/processes/{$process->id}/download/{$fileKey}");
            return ['url' => $url];
        } else {
            return response(['error' => __('Unable to Export Process')], 500) ;
        }
    }

    /**
     * Import the specified process.
     *
     * @param $process
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/processes/import",
     *     summary="Import a process",
     *     operationId="importProcess",
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
    public function import(Request $request)
    {
        $success = ImportProcess::dispatchNow($request->file('file')->get());
        return ['status' => $success];
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

}
