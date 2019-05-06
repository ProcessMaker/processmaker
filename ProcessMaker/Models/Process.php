<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ProcessMaker\AssignmentRules\PreviousTaskAssignee;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Traits\ProcessStartEventAssignmentsTrait;
use ProcessMaker\Traits\ProcessTaskAssignmentsTrait;
use ProcessMaker\Traits\ProcessTimerEventsTrait;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use ProcessMaker\Query\Traits\PMQL;

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
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ProcessEditable",
 *   @OA\Property(property="process_category_id", type="string", format="id"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="Process",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/ProcessEditable"),
 *       @OA\Schema(
 *           @OA\Property(property="user_id", type="string", format="id"),
 *           @OA\Property(property="id", type="string", format="id"),
 *           @OA\Property(property="created_at", type="string", format="date-time"),
 *           @OA\Property(property="updated_at", type="string", format="date-time"),
 *       )
 *   }
 * )
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
 * )
 * @OA\Schema(
 *     schema="ProcessWithStartEvents",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Process"),
 *         @OA\Schema(
 *         @OA\Property(
 *             property="startEvents",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ProcessStartEvents"),
 *         )),
 *     },
 * )
 */
class Process extends Model implements HasMedia
{
    use HasMediaTrait;
    use SerializeToIso8601;
    use SoftDeletes;
    use ProcessTaskAssignmentsTrait;
    use ProcessTimerEventsTrait;
    use ProcessStartEventAssignmentsTrait;
    use PMQL;

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
        'bpmn'
    ];

    /**
     * Parsed process BPMN definitions.
     *
     * @var \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    private $bpmnDefinitions;

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
            'name' => ['required', $unique],
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

    /**
     * Get the process definitions from BPMN field.
     *
     * @param bool $forceParse
     *
     * @return \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    public function getDefinitions($forceParse = false)
    {
        if ($forceParse || empty($this->bpmnDefinitions)) {
            $this->bpmnDefinitions = app(BpmnDocumentInterface::class, ['process' => $this]);
            if ($this->bpmn) {
                $this->bpmnDefinitions->loadXML($this->bpmn);
                //Load the collaborations if exists
                $collaborations = $this->bpmnDefinitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'collaboration');
                foreach ($collaborations as $collaboration) {
                    $collaboration->getBpmnElementInstance();
                }
            }
        }
        return $this->bpmnDefinitions;
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

        switch ($assignmentType) {
            case 'group':
                $user = $this->getNextUserFromGroupAssignment($activity->getId());
                break;
            case 'user':
                $user = $this->getNextUserAssignment($activity->getId());
                break;
            case 'requester':
                $user = $token->getInstance()->user_id;
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
                            $user = $token->getInstance()->user_id;
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
        $definitions = $this->getDefinitions();
        $response = [];
        $startEvents = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'startEvent');
        foreach ($startEvents as $startEvent) {
            if ($nofilter || ($user && isset($permissions[$startEvent->getAttribute('id')]) && in_array($user->id, $permissions[$startEvent->getAttribute('id')]))) {
                $bpmnNode = $startEvent->getBpmnElementInstance();
                $properties = $bpmnNode->getProperties();
                $properties['ownerProcessId'] = $bpmnNode->getOwnerProcess()->getId();
                $properties['ownerProcessName'] = $bpmnNode->getOwnerProcess()->getName();
                $response[] = $properties;
            }
        }
        return $response;
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
                $hasTimerStartEvent = $hasTimerStartEvent || $definition instanceof TimerEventDefinitionInterface;
            }
        }
        return $hasTimerStartEvent;
    }
}
