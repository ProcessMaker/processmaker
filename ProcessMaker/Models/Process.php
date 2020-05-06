<?php

namespace ProcessMaker\Models;

use DOMElement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mustache_Engine;
use ProcessMaker\AssignmentRules\PreviousTaskAssignee;
use ProcessMaker\Contracts\ProcessModelInterface;
use ProcessMaker\Exception\InvalidUserAssignmentException;
use ProcessMaker\Exception\TaskDoesNotHaveRequesterException;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Exception\UserOrGroupAssignmentEmptyException;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Query\Traits\PMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasSelfServiceTasks;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\ProcessStartEventAssignmentsTrait;
use ProcessMaker\Traits\ProcessTaskAssignmentsTrait;
use ProcessMaker\Traits\ProcessTimerEventsTrait;
use ProcessMaker\Traits\ProcessTrait;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

/**
 * Represents a business process definition.
 *
 * @property string $id
 * @property string $process_category_id
 * @property string $user_id
 * @property string $bpmn
 * @property string $description
 * @property string $name
 * @property string $status
 * @property string start_events
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ProcessEditable",
 *   @OA\Property(property="process_category_id", type="string", format="id"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 *   @OA\Property(property="pause_timer_start", type="integer"),
 *   @OA\Property(property="cancel_screen_id", type="integer"),
 *   @OA\Property(property="has_timer_start_events", type="boolean"),
 * ),
 * @OA\Schema(
 *   schema="Process",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/ProcessEditable"),
 *       @OA\Schema(
 *           @OA\Property(property="user_id", type="string", format="id"),
 *           @OA\Property(property="id", type="string", format="id"),
 *           @OA\Property(property="deleted_at", type="string", format="date-time"),
 *           @OA\Property(property="created_at", type="string", format="date-time"),
 *           @OA\Property(property="updated_at", type="string", format="date-time"),
 *       )
 *   }
 * ),
 * @OA\Schema(
 *     schema="ProcessStartEvents",
 *     @OA\Schema(
 *         @OA\Property(property="eventDefinitions", type="object"),
 *         @OA\Property(property="parallelMultiple", type="boolean"),
 *         @OA\Property(property="outgoing", type="object"),
 *         @OA\Property(property="incoming", type="object"),
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="name", type="string"),
 *     )
 * ),
 * @OA\Schema(
 *     schema="ProcessWithStartEvents",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Process"),
 *         @OA\Schema(
 *         @OA\Property(
 *             property="startEvents",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ProcessStartEvents"),
 *         ),
 *         @OA\Property(
 *             property="events",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ProcessStartEvents"),
 *         ))
 *     }
 * ),
 *
 * @OA\Schema(
 *     schema="ProcessImport",
 *     allOf={
 *      @OA\Schema(ref="#/components/schemas/ProcessEditable"),
 *      @OA\Schema(
 *         @OA\Property( property="status", type="object"),
 *         @OA\Property( property="assignable", type="array", @OA\Items(type="string") )
 *      )
 *    }
 * ),
 *
 * @OA\Schema(
 *   schema="CreateNewProcess",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/ProcessEditable"),
 *       @OA\Schema(ref="#/components/schemas/Process"),
 *       @OA\Schema(
 *           @OA\Property(property="notifications", type="array", @OA\Items(type="string")),
 *       )
 *   }
 * )
 */
class Process extends Model implements HasMedia, ProcessModelInterface
{
    use HasMediaTrait;
    use SerializeToIso8601;
    use SoftDeletes;
    use ProcessTaskAssignmentsTrait;
    use HasVersioning;
    use ProcessTimerEventsTrait;
    use ProcessStartEventAssignmentsTrait;
    use HideSystemResources;
    use PMQL;
    use HasCategories;
    use HasSelfServiceTasks;
    use ProcessTrait;

    const categoryClass = ProcessCategory::class;

    protected $connection = 'processmaker';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'user_id',
        'created_at',
        'updated_at',
        'has_timer_start_events',
        'warnings'
    ];

    /**
     * The attributes that are dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
        'bpmn',
        'svg',
    ];

    public $requestNotifiableTypes = [
        'requester',
        'assignee',
        'participants',
    ];

    public $requestNotificationTypes = [
        'started',
        'canceled',
        'completed',
    ];

    public $taskNotifiableTypes = [
        'requester',
        'assignee',
        'participants',
    ];

    public $taskNotificationTypes = [
        'assigned',
        'completed',
        'due',
    ];

    protected $appends = [
        'has_timer_start_events',
    ];

    protected $casts = [
        'start_events' => 'array',
        'warnings' => 'array',
        'self_service_tasks' => 'array',
        'signal_events' => 'array',
    ];

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_id');
    }

    /**
     * Notification settings of the process.
     *
     * @return HasMany
     */
    public function notification_settings()
    {
        return $this->hasMany(ProcessNotificationSetting::class);
    }

    /**
     * Notification settings of the process.
     *
     * @return object
     */
    public function getNotificationsAttribute()
    {
        $array = [];

        foreach ($this->requestNotifiableTypes as $notifiable) {
            foreach ($this->requestNotificationTypes as $notification) {
                $setting = $this->notification_settings()
                    ->whereNull('element_id')
                    ->where('notifiable_type', $notifiable)
                    ->where('notification_type', $notification)->get();

                if ($setting->count()) {
                    $value = true;
                } else {
                    $value = false;
                }

                $array[$notifiable][$notification] = $value;
            }
        }

        return (object)$array;
    }

    /**
     * Task notification settings of the process.
     *
     * @return object
     */
    public function getTaskNotificationsAttribute()
    {
        $array = [];

        $elements = $this->notification_settings()
            ->whereNotNull('element_id')
            ->get();

        foreach ($elements->groupBy('element_id') as $group) {
            $elementId = $group->first()->element_id;
            foreach ($this->taskNotifiableTypes as $notifiable) {
                foreach ($this->taskNotificationTypes as $notification) {
                    $setting = $group->where('notifiable_type', $notifiable)
                                     ->where('notification_type', $notification);

                    if ($setting->count()) {
                        $value = true;
                    } else {
                        $value = false;
                    }

                    $array[$elementId][$notifiable][$notification] = $value;
                }
            }
        }

        return (object)$array;
    }

    /**
     *  Cancel Screen of the process.
     *
     * @return BelongsTo
     */
    public function cancelScreen()
    {
        return $this->belongsTo(Screen::class, 'cancel_screen_id');
    }

    /**
     * Validation rules.
     *
     * @param null $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('processes')->ignore($existing);

        return [
            'name' => ['required', $unique, 'alpha_spaces'],
            'description' => 'required',
            'status' => 'in:ACTIVE,INACTIVE',
            'process_category_id' => 'exists:process_categories,id',
            'bpmn' => 'nullable',
        ];
    }

    /**
     * Get the creator/author of this process.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the users who can start this process
     *
     * @param string|null $node If null get START from any node
     */
    public function usersCanStart($node = null)
    {
        $relationship = $this->morphedByMany('ProcessMaker\Models\User', 'processable')
            ->wherePivot('method', 'START');
        $relationship = $node === null ? $relationship : $relationship->wherePivot('node', $node);
        return $relationship;
    }

    /**
     * Get the groups who can start this process
     *
     * @param string|null $node If null get START from any node
     */
    public function groupsCanStart($node = null)
    {
        $relationship = $this->morphedByMany('ProcessMaker\Models\Group', 'processable')
            ->wherePivot('method', 'START');
        $relationship = $node === null ? $relationship : $relationship->wherePivot('node', $node);
        return $relationship;
    }

    /**
     * Get the users who can start this process
     *
     */
    public function usersCanCancel()
    {
        return $this->morphedByMany('ProcessMaker\Models\User', 'processable')->wherePivot('method', 'CANCEL');
    }

    /**
     * Get the groups who can start this process
     *
     */
    public function groupsCanCancel()
    {
        return $this->morphedByMany('ProcessMaker\Models\Group', 'processable')->wherePivot('method', 'CANCEL');
    }

    /**
     * Get the users who can start this process
     *
     */
    public function usersCanEditData()
    {
        return $this->morphedByMany('ProcessMaker\Models\User', 'processable')->wherePivot('method', 'EDIT_DATA');
    }

    /**
     * Get the groups who can start this process
     *
     */
    public function groupsCanEditData()
    {
        return $this->morphedByMany('ProcessMaker\Models\Group', 'processable')->wherePivot('method', 'EDIT_DATA');
    }

    /**
     * Scope a query to include only active processes
     *
     */
    public function scopeActive($query)
    {
        return $query->where('processes.status', 'ACTIVE');
    }

    /**
     * Scope a query to include only inactive processes
     *
     */
    public function scopeInactive($query)
    {
        return $query->where('processes.status', 'INACTIVE');
    }

    public function getCollaborations()
    {
        $this->bpmnDefinitions = app(BpmnDocumentInterface::class, ['process' => $this]);
        if ($this->bpmn) {
            $this->bpmnDefinitions->loadXML($this->bpmn);
            //Load the collaborations if exists
            return $this->bpmnDefinitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'collaboration');
        }
    }

    /**
     * Get the path of the process templates.
     *
     * @return string
     */
    public static function getProcessTemplatesPath()
    {
        return Storage::disk('process_templates')->path('');
    }

    /**
     * Get a process template by name.
     *
     * @param string $name
     *
     * @return string
     */
    public static function getProcessTemplate($name)
    {
        return Storage::disk('process_templates')->get($name);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function requests()
    {
        return $this->hasMany(ProcessRequest::class);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function collaborations()
    {
        return $this->hasMany(ProcessCollaboration::class);
    }

    /**
     * Get the user to whom to assign a task.
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return User
     */
    public function getNextUser(ActivityInterface $activity, ProcessRequestToken $token)
    {
        $default = $activity instanceof ScriptTaskInterface
        || $activity instanceof ServiceTaskInterface ? 'script' : 'requester';
        $assignmentType = $activity->getProperty('assignment', $default);

        $userByRule = $this->getNextUserByRule($activity, $token);
        if ($userByRule !== null) {
            return $userByRule;
        }

        $definitions = $token->getInstance()->process->getDefinitions();
        $properties = $definitions->findElementById($activity->getId())->getBpmnElementInstance()->getProperties();
        $assignmentLock = array_key_exists('assignmentLock', $properties) ? $properties['assignmentLock']  : false;

        if (filter_var($assignmentLock, FILTER_VALIDATE_BOOLEAN) === true) {
            $user = $this->getLastUserAssignedToTask($activity->getId(), $token->getInstance()->getId());
            if ($user) {
                return User::where('id', $user)->first();
            }
        }

        switch ($assignmentType) {
            case 'user_group':
            case 'group':
                $user = $this->getNextUserFromGroupAssignment($activity->getId());
                break;
            case 'user':
                $user = $this->getNextUserAssignment($activity->getId());
                break;
            case 'user_by_id':
                $user = $this->getNextUserFromVariable($activity, $token);
                break;
            case 'requester':
                $user = $this->getRequester($token);
                break;
            case 'previous_task_assignee':
                $rule = new PreviousTaskAssignee();
                $user = $rule->getNextUser($activity, $token, $this, $token->getInstance());
                break;
            case 'manual':
            case 'self_service':
                $user = null;
                break;
            case 'script':
            default:
                $user = null;
        }
        return $user ? User::where('id', $user)->first() : null;
    }

    /**
     * If the assignment type is user_by_id, we need to parse
     * mustache syntax with the current data to get the user
     * that should be assigned
     *
     * @param ProcessRequestToken $token
     * @return User $user
     * @throws InvalidUserAssignmentException
     */
    private function getNextUserFromVariable($activity, $token)
    {
        $userExpression = $activity->getProperty('assignedUsers');
        $instanceData = $token->getInstance()->getDataStore()->getData();

        $mustache = new Mustache_Engine();
        $userId = $mustache->render($userExpression, $instanceData);

        $user = User::find($userId);
        if (!$user) {
            throw new InvalidUserAssignmentException($userExpression, $userId);
        }
        return $user->id;
    }

    /**
     * Get the next user in a cyclical assignment.
     *
     * @param string $processTaskUuid
     *
     * @return binary
     * @throws TaskDoesNotHaveUsersException
     */
    private function getNextUserFromGroupAssignment($processTaskUuid, $users = null)
    {
        $last = ProcessRequestToken::where('process_id', $this->id)
            ->where('element_id', $processTaskUuid)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        if ($users === null) {
            $users = $this->getAssignableUsers($processTaskUuid);
        }
        if (empty($users)) {
            throw new UserOrGroupAssignmentEmptyException($processTaskUuid);
        }
        sort($users);
        if ($last) {
            foreach ($users as $user) {
                if ($user > $last->user_id) {
                    return $user;
                }
            }
        }
        return $users[0];
    }

    /**
     * Given a request, returns the last user assigned to a task. If it is the
     * first time that the task is assigned, null is returned.
     *
     * @param string $processTaskUuid
     *
     * @return binary
     * @throws TaskDoesNotHaveUsersException
     */
    private function getLastUserAssignedToTask($processTaskUuid, $processRequestId)
    {
        $last = ProcessRequestToken::where('process_id', $this->id)
            ->where('element_id', $processTaskUuid)
            ->where('process_request_id', $processRequestId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $last ? $last->user_id : null;
    }

    /**
     * Get the next user in a user assignment.
     *
     * @param string $processTaskUuid
     *
     * @return binary
     * @throws TaskDoesNotHaveUsersException
     */
    private function getNextUserAssignment($processTaskUuid, $users = null)
    {
        $last = ProcessRequestToken::where('process_id', $this->id)
            ->where('element_id', $processTaskUuid)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($users === null) {
            $users = $this->getAssignableUsers($processTaskUuid);
        }
        if (empty($users)) {
            throw new TaskDoesNotHaveUsersException($processTaskUuid);
        }
        sort($users);
        if ($last) {
            foreach ($users as $user) {
                if ($user > $last->user_id) {
                    return $user;
                }
            }
        }
        return $users[0];
    }

    /**
     * Get the next user if some special assignment is true
     *
     * @param string $processTaskUuid
     *
     * @return binary
     * @throws TaskDoesNotHaveUsersException
     */
    private function getNextUserByRule($activity, $token)
    {
        $assignmentRules = $activity->getProperty('assignmentRules', null);

        $instanceData = $token->getInstance()->getDataStore()->getData();

        if ($assignmentRules && $instanceData) {
            $list = json_decode($assignmentRules);
            $list = ($list === null) ? [] : $list;
            foreach ($list as $item) {
                $formalExp = new FormalExpression();
                $formalExp->setLanguage('FEEL');
                $formalExp->setBody($item->expression);
                $eval = $formalExp($instanceData);
                if ($eval) {
                    switch ($item->type) {
                        case 'user_group':
                            $users =  [];
                            foreach ($item->assignee->users as $user) {
                                $users[$user] = $user;
                            }
                            foreach ($item->assignee->groups as $group) {
                                $this->getConsolidatedUsers($group, $users);
                            }
                            $user = $this->getNextUserFromGroupAssignment(
                                $activity->getId(),
                                $users
                            );
                            break;
                        case 'group':
                            $users = [];
                            $user = $this->getNextUserFromGroupAssignment(
                                $activity->getId(),
                                $this->getConsolidatedUsers($item->assignee, $users)
                            );
                            break;
                        case 'user':
                            $user = $item->assignee;
                            break;
                        case 'requester':
                            $user = $this->getRequester($token);
                            break;
                        case 'manual':
                        case 'self_service':
                            $user = null;
                            break;
                        case 'user_by_id':
                            $mustache = new Mustache_Engine();
                            $assigneeId = $mustache->render($item->assignee, $instanceData);
                            $user = $assigneeId;
                            break;
                        case 'script':
                        default:
                            $user = null;
                    }
                    return $user ? User::where('id', $user)->first() : null;
                }
            }
        }
        return null;
    }

    /**
     * Get an array of all assignable users to a task.
     *
     * @param string $processTaskUuid
     *
     * @return array
     */
    public function getAssignableUsers($processTaskUuid)
    {
        $assignments = ProcessTaskAssignment::select(['assignment_id', 'assignment_type'])
            ->where('process_id', $this->id)
            ->where('process_task_id', $processTaskUuid)
            ->get();
        $users = [];
        foreach ($assignments as $assignment) {
            if ($assignment->assignment_type === User::class) {
                $users[$assignment->assignment_id] = $assignment->assignment_id;
            } else { // Group::class
                $this->getConsolidatedUsers($assignment->assignment_id, $users);
            }
        }
        return array_values($users);
    }

    /**
     * Get a consolidated list of users within groups.
     *
     * @param binary $group_id
     * @param array $users
     *
     * @return array
     */
    public function getConsolidatedUsers($group_id, array &$users)
    {
        $groupMembers = GroupMember::where('group_id', $group_id)->get();
        foreach ($groupMembers as $groupMember) {
            if ($groupMember->member->status !== 'ACTIVE') {
                continue;
            }
            if ($groupMember->member_type === User::class) {
                $users[$groupMember->member_id] = $groupMember->member_id;
            } else {
                $this->getConsolidatedUsers($groupMember->member_id, $users);
            }
        }
        return $users;
    }

    /**
     * Get a list of the process start events.
     *
     * @return array
     */
    public function getStartEvents($filterWithPermissions = false)
    {
        $user = Auth::user();
        $isAdmin = $user ? $user->is_administrator : false;
        $permissions = $filterWithPermissions && !$isAdmin ? $this->getStartEventPermissions() : [];
        $nofilter = $isAdmin || !$filterWithPermissions;
        $response = [];
        if (!isset($this->start_events)) {
            $this->start_events = $this->getUpdatedStartEvents();
        }
        foreach ($this->start_events as $startEvent) {
            $id = $startEvent['id'];
            if ($nofilter || ($user && isset($permissions[$id]) && in_array($user->id, $permissions[$id]))) {
                $response[] = $startEvent;
            }
        }
        return $response;
    }

    /**
     * Get an updated list of start events from BPMN
     *
     * @return array
     */
    public function getUpdatedStartEvents()
    {
        $response = [];
        if (empty($this->bpmn)) {
            return $response;
        }
        $definitions = new BpmnDocument();
        $definitions->loadXML($this->bpmn);
        $startEvents = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'startEvent');
        foreach ($startEvents as $startEvent) {
            $properties = $this->nodeAttributes($startEvent);
            $properties['ownerProcessId'] = $startEvent->parentNode->getAttribute('id');
            $properties['ownerProcessName'] = $startEvent->parentNode->getAttribute('name');
            $startEvent->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'timerEventDefinition');
            $properties['eventDefinitions'] = [];
            foreach ($startEvent->childNodes as $node) {
                if (substr($node->localName, -15) === 'EventDefinition') {
                    $eventDefinition = $this->nodeAttributes($node);
                    $eventDefinition['$type'] = $node->localName;
                    $properties['eventDefinitions'][] = $eventDefinition;
                }
            }
            $response[] = $properties;
        }
        return $response;
    }

    /**
     * Get node element attributes
     *
     * @param DOMElement $node
     *
     * @return array
     */
    private function nodeAttributes(DOMElement $node)
    {
        $array = [];
        foreach ($node->attributes as $attribute) {
            $array[$attribute->localName] = $attribute->nodeValue;
        }
        return $array;
    }

    public function getIntermediateCatchEvents()
    {
        $definitions = $this->getDefinitions();
        $response = [];
        $catchEvents = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'intermediateCatchEvent');
        foreach ($catchEvents as $catchEvent) {
            $response[] = $catchEvent->getBpmnElementInstance()->getProperties();
        }
        return $response;
    }

    /**
     * Update BPMN content and reset bpmnDefinitions
     *
     * @param string $value
     */
    public function setBpmnAttribute($value)
    {
        $this->bpmnDefinitions = null;
        $this->attributes['bpmn'] = $value;
    }

    /**
     * Get permissions by start event.
     *
     * @return array
     */
    private function getStartEventPermissions()
    {
        $permissions = [];
        foreach ($this->usersCanStart()->withPivot('node')->get() as $user) {
            $permissions[$user->pivot->node][$user->id] = $user->id;
        }
        foreach ($this->groupsCanStart()->withPivot('node')->get() as $group) {
            $users = [];
            $this->getConsolidatedUsers($group->id, $users);
            isset($permissions[$group->pivot->node]) ?: $permissions[$group->pivot->node] = [];
            $permissions[$group->pivot->node] = $permissions[$group->pivot->node] + $users;
        }
        return $permissions;
    }

    /**
     * Process events relationship.
     *
     * @return \ProcessMaker\Models\ProcessEvents
     */
    public function events()
    {
        $query = $this->newQuery();
        $query->where('id', $this->id);
        return new ProcessEvents($query, $this);
    }

    /**
     * Get the associated versions
     */
    public function versions()
    {
        return $this->hasMany(ProcessVersion::class);
    }

    /**
     * Assignments of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(ProcessTaskAssignment::class);
    }

    /**
     * Return true if the process has an Timer Start Event
     *
     * @return boolean
     */
    public function getHasTimerStartEventsAttribute()
    {
        $hasTimerStartEvent = false;
        foreach ($this->getStartEvents() as $event) {
            foreach ($event['eventDefinitions'] as $definition) {
                $hasTimerStartEvent = $hasTimerStartEvent || $definition['$type'] === 'timerEventDefinition';
            }
        }
        return $hasTimerStartEvent;
    }

    /**
     * Get the requester of the current token
     *
     * @param string $processTaskUuid
     *
     * @return Integer $user_id
     * @throws TaskDoesNotHaveRequesterException
     */
    private function getRequester($token)
    {
        $processRequest = $token->getInstance();

        // Check for anonymous web entry
        $startEvent = $processRequest->tokens()->where('element_type', 'startEvent')->firstOrFail();
        $canBeAnonymous = false;
        if (isset($startEvent->getDefinition()['config'])) {
            $config = json_decode($startEvent->getDefinition()['config'], true);
            if ($config && $config['web_entry'] && $config['web_entry']['mode'] === 'ANONYMOUS') {
                $canBeAnonymous = true;
            }
        }

        if (!$processRequest->user_id && !$canBeAnonymous) {
            throw new TaskDoesNotHaveRequesterException();
        }

        return $processRequest->user_id;
    }

    /**
     * Check the BPMN and convert not supported or extended features
     *
     */
    public function convertFromExternalBPM()
    {
        if (!$this->bpmn) {
            return;
        }
        $warnings = $this->warnings;
        $document = new BpmnDocument();
        $document->loadXML($this->bpmn);
        $conversions = 0;
        // Replace subProcess by callActivity
        $subProcesses = $document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'subProcess');
        while ($subProcess = $subProcesses->item(0)) {
            $conversions++;
            $name = $subProcess->getAttribute('name');
            $callActivity = $this->createCallActivityFrom($subProcess);
            $subProcess->parentNode->replaceChild($callActivity, $subProcess);
            $warnings[] = [
                'title' => __('Element conversion'),
                'text' => __('SubProcess Conversion', ['name' => $name]),
            ];
        }
        // Replace sendTask to scriptTask
        $sendTasks = $document->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'sendTask');
        while ($sendTask = $sendTasks->item(0)) {
            $conversions++;
            $name = $sendTask->getAttribute('name');
            $scriptTask = $this->cloneNodeAs($sendTask, 'scriptTask');
            $sendTask->parentNode->replaceChild($scriptTask, $sendTask);
            $warnings[] = [
                'title' => __('Element conversion'),
                'text' => __('SendTask Conversion', ['name' => $name]),
            ];
        }
        if ($conversions) {
            $this->bpmn = $document->saveXml();
            $this->bpmnDefinitions = null;
        }
        $this->warnings = $warnings;
    }

    /**
     * Convert a subProcess into a callActivity
     *
     * @param BPMNElement $subProcess
     * @return void
     */
    private function createCallActivityFrom($subProcess)
    {
        $element = $this->cloneNodeAs($subProcess, 'callActivity', ['outgoing', 'incoming'], [], ['triggeredByEvent']);

        $definitions = $subProcess->ownerDocument->firstChild->cloneNode(false);
        $diagram = $subProcess->ownerDocument->getElementsByTagName('BPMNDiagram')->item(0)->cloneNode(true);
        $subProcessClone = $this->cloneNodeAs($subProcess, 'process', [], ['outgoing', 'incoming'], ['triggeredByEvent']);
        $definitions->appendChild($subProcessClone);
        $definitions->appendChild($diagram);

        $subProcessBpmn = $subProcessClone->ownerDocument->saveXml($definitions);

        $name = $subProcessClone->getAttribute('name');
        $duplicated = Process::where('name', 'like', $name . '%')
            ->orderBy(DB::raw('LENGTH(name), name'))
            ->get();
        if ($duplicated->count()) {
            $duplicated = $duplicated->last();
            $number = intval(substr($duplicated->name, strlen($name))) + 1;
            $name = $name . ' (' . $number . ')';
        }
        $process = new Process([
            'name' => $name,
            'bpmn' => $subProcessBpmn,
            'description' => $subProcessClone->getAttribute('name'),
        ]);
        $process->user_id = $this->user_id;
        $process->process_category_id = $this->process_category_id;
        $process->save();
        $bpmnProcess = $process->getDefinitions()->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process')->item(0);
        $element->setAttribute('calledElement', $bpmnProcess->getAttribute('id') . '-' . $process->id);
        return $element;
    }

    /**
     * Create a clone of a BPMNElement with a different nodeName
     *
     * @param BPMNElement $node
     * @param string $newNodeName
     * @param array $include
     * @param array $exclude
     * @param array $excludeAttributes
     *
     * @return BPMNElement
     */
    public function cloneNodeAs($node, $newNodeName, $include = [], $exclude = [], $excludeAttributes = [])
    {
        $newnode = $node->ownerDocument->createElementNS(BpmnDocument::BPMN_MODEL, $newNodeName);
        foreach ($node->childNodes as $child) {
            if ($child->nodeName !== '#text') {
                $shortName = explode(':', $child->nodeName);
                $shortName = count($shortName) === 2 ? $shortName[1] : $shortName[0];
                if ($include && !in_array($shortName, $include)) {
                    continue;
                }
                if ($exclude && in_array($shortName, $exclude)) {
                    continue;
                }
            }
            $child = $child->cloneNode(true);
            $newnode->appendChild($child);
        }
        foreach ($node->attributes as $attrName => $attrNode) {
            if (!in_array($attrName, $excludeAttributes)) {
                $newnode->setAttribute($attrName, $attrNode->nodeValue);
            }
        }
        return $newnode;
    }

    /**
     * Set multiple|single categories to the process
     *
     * @param string $value
     */
    public function setProcessCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'process_category_id');
    }

    /**
     * Get multiple|single categories of the process
     *
     * @param string $value
     */
    public function getProcessCategoryIdAttribute($value)
    {
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }

    /**
     * Get the latest version of the process
     *
     */
    public function getLatestVersion()
    {
        return $this->versions()->orderBy('id', 'desc')->first();
    }

    /**
     * Check if process is valid for execution
     *
     * @return boolean
     */
    public function isValidForExecution()
    {
        return empty($this->warnings) && !empty($this->getLatestVersion());
    }

    /**
     * Get the unique Signal References for the Signal Start Events.
     *
     * @return array
     */
    public function getUpdatedStartEventsSignalEvents(): array
    {
        $eventDefinitions = collect($this->start_events)->pluck('eventDefinitions')->flatten(1);

        $signalEventDefinitions = $eventDefinitions->filter(function ($eventDefinition) {
            return $eventDefinition['$type'] === 'signalEventDefinition';
        });

        $signalReferences = $signalEventDefinitions->pluck('signalRef')->unique();

        return $signalReferences->toArray();
    }
}
