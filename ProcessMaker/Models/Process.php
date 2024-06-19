<?php

namespace ProcessMaker\Models;

use DOMElement;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Mustache_Engine;
use ProcessMaker\AssignmentRules\PreviousTaskAssignee;
use ProcessMaker\AssignmentRules\ProcessManagerAssigned;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Contracts\ProcessModelInterface;
use ProcessMaker\Exception\InvalidUserAssignmentException;
use ProcessMaker\Exception\TaskDoesNotHaveRequesterException;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Facades\WorkflowUserManager;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Nayra\Bpmn\Models\Activity;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Managers\WorkflowManagerDefault;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Package\WebEntry\Models\WebentryRoute;
use ProcessMaker\Rules\BPMNValidation;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasSelfServiceTasks;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\ProcessStartEventAssignmentsTrait;
use ProcessMaker\Traits\ProcessTaskAssignmentsTrait;
use ProcessMaker\Traits\ProcessTimerEventsTrait;
use ProcessMaker\Traits\ProcessTrait;
use ProcessMaker\Traits\ProjectAssetTrait;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Represents a business process definition.
 *
 * @property string $id
 * @property string $process_category_id
 * @property string $user_id
 * @property string $bpmn
 * @property string $description
 * @property string $name
 * @property string $case_title
 * @property string $status
 * @property array $start_events
 * @property int $manager_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @method static Process find(string $id)
 *
 * @OA\Schema(
 *   schema="ProcessEditable",
 *   @OA\Property(property="process_category_id", type="integer", format="id"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="case_title", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE", "ARCHIVED"}),
 *   @OA\Property(property="pause_timer_start", type="integer"),
 *   @OA\Property(property="cancel_screen_id", type="integer"),
 *   @OA\Property(property="has_timer_start_events", type="boolean"),
 *   @OA\Property(property="request_detail_screen_id", type="integer", format="id"),
 *   @OA\Property(property="is_valid", type="integer"),
 *   @OA\Property(property="package_key", type="string"),
 *   @OA\Property(property="start_events", type="array", @OA\Items(ref="#/components/schemas/ProcessStartEvents")),
 *   @OA\Property(property="warnings", type="string"),
 *   @OA\Property(property="self_service_tasks", type="object"),
 *   @OA\Property(property="signal_events", type="array", @OA\Items(type="object")),
 *   @OA\Property(property="category", type="object", @OA\Schema(ref="#/components/schemas/ProcessCategory")),
 *   @OA\Property(property="manager_id", type="integer", format="id"),
 * ),
 * @OA\Schema(
 *   schema="Process",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/ProcessEditable"),
 *       @OA\Schema(
 *           @OA\Property(property="user_id", type="integer", format="id"),
 *           @OA\Property(property="id", type="string", format="id"),
 *           @OA\Property(property="deleted_at", type="string", format="date-time"),
 *           @OA\Property(property="created_at", type="string", format="date-time"),
 *           @OA\Property(property="updated_at", type="string", format="date-time"),
 *           @OA\Property(property="notifications", type="object"),
 *           @OA\Property(property="task_notifications", type="object"),
 *       )
 *   }
 * ),
 * @OA\Schema(
 *     schema="ProcessStartEvents",
 *     @OA\Property(property="eventDefinitions", type="object"),
 *     @OA\Property(property="parallelMultiple", type="boolean"),
 *     @OA\Property(property="outgoing", type="object"),
 *     @OA\Property(property="incoming", type="object"),
 *     @OA\Property(property="id", type="string"),
 *     @OA\Property(property="name", type="string"),
 * ),
 * @OA\Schema(
 *     schema="ProcessWithStartEvents",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Process"),
 *         @OA\Schema(
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
 *         @OA\Property(property="status", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="assignable", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="process", @OA\Schema(ref="#/components/schemas/Process"))
 *      )
 *    }
 * ),
 * @OA\Schema(
 *     schema="ProcessAssignments",
 *     @OA\Property(property="assignable", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="cancel_request", type="object"),
 *     @OA\Property(property="edit_data", type="object"),
 * )
 */
class Process extends ProcessMakerModel implements HasMedia, ProcessModelInterface
{
    use InteractsWithMedia;
    use SerializeToIso8601;
    use SoftDeletes;
    use ProcessTaskAssignmentsTrait;
    use HasVersioning;
    use ProcessTimerEventsTrait;
    use ProcessStartEventAssignmentsTrait;
    use HideSystemResources;
    use ExtendedPMQL;
    use HasCategories;
    use HasSelfServiceTasks;
    use ProcessTrait;
    use Exportable;
    use ProjectAssetTrait;

    const categoryClass = ProcessCategory::class;

    const ASSIGNMENT_PROCESS = 'Assignment process';

    const NOT_ASSIGNABLE_USER_STATUS = ['INACTIVE', 'OUT_OF_OFFICE'];

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
        'warnings',
    ];

    /**
     * The attributes that are dates.
     *
     * @var array
     */
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
        'manager',
    ];

    public $requestNotificationTypes = [
        'started',
        'canceled',
        'completed',
        'error',
        'comment',
    ];

    public $taskNotifiableTypes = [
        'requester',
        'assignee',
        'participants',
        'manager',
    ];

    public $taskNotificationTypes = [
        'assigned',
        'completed',
        'due',
    ];

    protected $appends = [
        'has_timer_start_events',
        'projects',
    ];

    protected $casts = [
        'start_events' => 'array',
        'warnings' => 'array',
        'self_service_tasks' => 'array',
        'signal_events' => 'array',
        'conditional_events' => 'array',
        'properties' => 'array',
    ];

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_id')->withDefault();
    }

    /**
     * Get the associated projects
     */
    public function projects()
    {
        if (!class_exists('ProcessMaker\Package\Projects\Models\Project')) {
            // return an empty collection
            return new HasMany($this->newQuery(), $this, '', '');
        }

        return $this->belongsToMany('ProcessMaker\Package\Projects\Models\Project',
            'project_assets',
            'asset_id',
            'project_id',
            'id',
            'id'
        )->wherePivot('asset_type', static::class);
    }

    // Define the relationship with the ProjectAsset model
    public function projectAssets()
    {
        return $this->belongsToMany('ProcessMaker\Package\Projects\Models\ProjectAsset',
            'project_assets', 'asset_id', 'project_id')
            ->withPivot('asset_type')
            ->wherePivot('asset_type', static::class)->withTimestamps();
    }

    public function projectAsset()
    {
        return $this->belongsToMany('ProcessMaker\Package\Projects\Models\ProjectAsset',
            'project_assets',
            'asset_id',
            'project_id',
        )->withTimeStamps();
    }

    /**
     * Returns a single record from the `Alternative` model
     */
    public function alternativeInfo()
    {
        return $this->hasOne('ProcessMaker\Package\PackageABTesting\Models\Alternative', 'process_id', 'id');
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
     * Get the associated embed
     */
    public function embed()
    {
        return $this->hasMany(Embed::class, 'model_id', 'id');
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

        return (object) $array;
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

        return (object) $array;
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
            'status' => 'in:ACTIVE,INACTIVE,ARCHIVED',
            'process_category_id' => 'exists:process_categories,id',
            'bpmn' => 'nullable',
            'case_title' => 'nullable|max:200',
            'alternative' => 'nullable|in:A,B',
        ];
    }

    /**
     * Get the creator/author of this process.
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

        return $node === null ? $relationship : $relationship->wherePivot('node', $node);
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

        return $node === null ? $relationship : $relationship->wherePivot('node', $node);
    }

    /**
     * Scope a query to include only active and inactive but not archived processes
     */
    public function scopeNotArchived($query)
    {
        return $query->whereIn('processes.status', ['ACTIVE', 'INACTIVE']);
    }

    /**
     * Scope a query to include only active processes
     */
    public function scopeActive($query)
    {
        return $query->where('processes.status', 'ACTIVE');
    }

    /**
     * Scope a query to include only inactive processes
     */
    public function scopeInactive($query)
    {
        return $query->where('processes.status', 'INACTIVE');
    }

    /**
     * Scope a query to include only archived processes
     */
    public function scopeArchived($query)
    {
        return $query->where('processes.status', 'ARCHIVED');
    }

    /**
     * Scope a query to include a specific category
     */
    public function scopeProcessCategory($query, int $id)
    {
        return $query->whereHas('categories', function ($query) use ($id) {
            $query->where('process_categories.id', $id);
        });
    }

    /**
     * Scope a query to include a specific category
     * @param string $status
     */
    public function scopeCategoryStatus($query, $status)
    {
        if (!empty($status)) {
            return $query->whereHas('categories', function ($query) use ($status) {
                $query->where('process_categories.status', $status);
            });
        }
    }

    /**
     * Load the collaborations if exists
     *
     * @deprecated Not used, does not have a process_version reference
     *
     * @return BpmnDocumentInterface
     */
    public function getCollaborations()
    {
        $this->bpmnDefinitions = app(BpmnDocumentInterface::class, ['process' => $this]);
        if ($this->bpmn) {
            $this->bpmnDefinitions->loadXML($this->bpmn);

            // Load the collaborations if exists
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
        $config = json_decode($activity->getProperty('config', '{}'), true);
        $escalateToManager = $config['escalateToManager'] ?? false;

        $definitions = $token->getInstance()->getVersionDefinitions();
        $properties = $definitions->findElementById($activity->getId())->getBpmnElementInstance()->getProperties();
        $assignmentLock = array_key_exists('assignmentLock', $properties ?? []) ? $properties['assignmentLock'] : false;

        $config = array_key_exists('config', $properties ?? []) ? json_decode($properties['config'], true) : [];
        $isSelfService = array_key_exists('selfService', $config ?? []) ? $config['selfService'] : false;

        if ($assignmentType === 'rule_expression') {
            $userByRule = $isSelfService ? null : $this->getNextUserByRule($activity, $token);
            if ($userByRule !== null) {
                $user = $this->scalateToManagerIfEnabled($userByRule->id, $activity, $token, $assignmentType);

                return $this->checkAssignment($token->processRequest, $activity, $assignmentType, $escalateToManager, $user ? User::where('id', $user)->first() : null);
            }
        }

        if (filter_var($assignmentLock, FILTER_VALIDATE_BOOLEAN) === true) {
            $user = $this->getLastUserAssignedToTask($activity->getId(), $token->getInstance()->getId());
            if ($user) {
                return $this->checkAssignment($token->processRequest, $activity, $assignmentType, $escalateToManager, User::where('id', $user)->first());
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
            case 'process_variable':
                $user = $this->getNextUserFromProcessVariable($activity, $token);
                break;
            case 'requester':
                $user = $this->getRequester($activity, $token);
                break;
            case 'previous_task_assignee':
                $rule = new PreviousTaskAssignee();
                $user = $rule->getNextUser($activity, $token, $this, $token->getInstance());
                break;
            case 'process_manager':
                $rule = new ProcessManagerAssigned();
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

        // If the self-service toggle is enabled the user must always be null
        if ($isSelfService && in_array($assignmentType, ['user_group', 'process_variable', 'rule_expression'])) {
            $user = null;
        }

        $user = $this->scalateToManagerIfEnabled($user, $activity, $token, $assignmentType);

        return $this->checkAssignment($token->getInstance(), $activity, $assignmentType, $escalateToManager, $user ? User::where('id', $user)->first() : null);
    }

    /**
     * If user assignment is not valid reassign to Process Manager
     *
     * @param ProcessRequest $request
     * @param ActivityInterface $activity
     * @param string $assignmentType
     * @param bool $escalateToManager
     * @param User|null $user
     *
     * @return User|null
     */
    private function checkAssignment(ProcessRequest $request, ActivityInterface $activity, $assignmentType, $escalateToManager, User $user = null)
    {
        $config = $activity->getProperty('config') ? json_decode($activity->getProperty('config'), true) : [];
        $selfServiceToggle = array_key_exists('selfService', $config ?? []) ? $config['selfService'] : false;
        $isSelfService = $selfServiceToggle || $assignmentType === 'self_service';

        if ($activity instanceof ScriptTaskInterface
            || $activity instanceof ServiceTaskInterface) {
            return $user;
        }
        if ($user === null) {
            if ($isSelfService && !$escalateToManager) {
                return null;
            }
            $user = $request->processVersion->manager;
            if (!$user) {
                throw new ThereIsNoProcessManagerAssignedException($activity);
            }
        }

        return $user;
    }

    private function scalateToManagerIfEnabled($user, $activity, $token, $assignmentType)
    {
        if ($user) {
            $assignmentProcess = self::where('name', self::ASSIGNMENT_PROCESS)->first();
            if (app()->bound('workflow.UserManager') && $assignmentProcess) {
                $config = json_decode($activity->getProperty('config', '{}'), true);
                $escalateToManager = $config['escalateToManager'] ?? false;
                if ($escalateToManager) {
                    $user = WorkflowUserManager::escalateToManager($token, $user);
                } else {
                    $res = (new WorkflowManagerDefault)->runProcess($assignmentProcess, 'assign', [
                        'user_id' => $user,
                        'process_id' => $this->id,
                        'request_id' => $token->getInstance()->getId(),
                    ]);
                    $user = $res['assign_to'];
                }
            }
        }

        return $user;
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
        try {
            $userExpression = $activity->getProperty('assignedUsers');

            $dataManager = new DataManager();
            $instanceData = $dataManager->getData($token);

            $mustache = new Mustache_Engine();
            $userId = $mustache->render($userExpression, $instanceData);

            $user = User::find($userId);
            if (!$user) {
                throw new InvalidUserAssignmentException($userExpression, $userId);
            }

            return $user->id;
        } catch (Exception $exception) {
            return null;
        }
    }

    /*
     * Used to assign a user when the task is assigned by variables that have lists
     * of users and groups
     */
    private function getNextUserFromProcessVariable($activity, $token)
    {
        // self service tasks should not have a next user
        if ($token->getSelfServiceAttribute()) {
            return null;
        }

        $usersVariable = $activity->getProperty('assignedUsers');
        $groupsVariable = $activity->getProperty('assignedGroups');

        $dataManager = new DataManager();
        $instanceData = $dataManager->getData($token);

        $assignedUsers = $usersVariable ? Arr::get($instanceData, $usersVariable) : [];
        $assignedGroups = $groupsVariable ? Arr::get($instanceData, $groupsVariable) : [];

        if (!is_array($assignedUsers)) {
            $assignedUsers = [$assignedUsers];
        }

        if (!is_array($assignedGroups)) {
            $assignedGroups = [$assignedGroups];
        }

        // We need to remove inactive users.
        $users = User::whereIn('id', array_unique($assignedUsers))->where('status', 'ACTIVE')->pluck('id')->toArray();

        // user in OUT_OF_OFFICE
        $outOfOffice = User::whereIn('id', array_unique($assignedUsers))->where('status', 'OUT_OF_OFFICE')->get();

        foreach ($outOfOffice as $user) {
            $delegation = $user->delegationUser()->pluck('id')->toArray();
            if ($delegation) {
                $users[] = $delegation[0];
            }
        }

        foreach ($assignedGroups as $groupId) {
            // getConsolidatedUsers already removes inactive users
            $this->getConsolidatedUsers($groupId, $users);
        }

        return $this->getNextUserFromGroupAssignment($activity->getId(), $users);
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
            return null;
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
            return null;
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
                            $users = [];
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
                            $user = $this->getRequester($activity, $token);
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
     * Evaluates each expression rule and returns the list of groups and users
     * that can be assigned to
     *
     * @param $activity
     * @param $token
     * @return array
     */
    public function getAssigneesFromExpressionRules($activity, $token)
    {
        $assignmentRules = $activity->getProperty('assignmentRules', null);
        $instanceData = $token->getInstance()->getDataStore()->getData();
        $groups = [];
        $users = [];
        $default = [];
        if ($assignmentRules && $instanceData) {
            $list = json_decode($assignmentRules);
            $list = ($list === null) ? [] : $list;
            foreach ($list as $item) {
                if (is_null($item->expression)) {
                    $default[] = $item;
                    continue;
                }
                $formalExp = new FormalExpression();
                $formalExp->setLanguage('FEEL');
                $formalExp->setBody($item->expression);
                $eval = $formalExp($instanceData);
                if ($eval) {
                    if ($item->type === 'group') {
                        $groups[] = $item->assignee;
                    } elseif ($item->type === 'user') {
                        $users[] = $item->assignee;
                    }
                }
            }

            // If no rule was applied, use the default configured user/group
            if (empty($users) && empty($groups)) {
                foreach ($default as $item) {
                    if ($item->type === 'group') {
                        $groups[] = $item->assignee;
                    } elseif ($item->type === 'user') {
                        $users[] = $item->assignee;
                    }
                }
            }
        }

        return compact('users', 'groups');
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
     * @param mixed $group_id
     * @param array $users
     *
     * @return array
     */
    public function getConsolidatedUsers($groupOrGroups, array &$users)
    {
        $isArray = is_array($groupOrGroups);
        if ($isArray) {
            $groupOrGroups = array_unique($groupOrGroups);
        }
        // Add the users from the groups
        GroupMember::select('member_id')
            ->where('member_type', User::class)
            ->when($isArray, function ($query) use ($groupOrGroups) {
                $query->whereIn('group_id', $groupOrGroups);
            }, function ($query) use ($groupOrGroups) {
                $query->where('group_id', $groupOrGroups);
            })
            ->leftjoin('users', 'users.id', '=', 'group_members.member_id')
            ->whereNotIn('users.status', Process::NOT_ASSIGNABLE_USER_STATUS)
            ->chunk(1000, function ($members) use (&$users) {
                $userIds = $members->pluck('member_id')->toArray();
                $users = array_unique(array_merge($users, $userIds));
            });

        // Add the users from the subgroups
        GroupMember::select('member_id')
            ->where('member_type', Group::class)
            ->when($isArray, function ($query) use ($groupOrGroups) {
                $query->whereIn('group_id', $groupOrGroups);
            }, function ($query) use ($groupOrGroups) {
                $query->where('group_id', $groupOrGroups);
            })
            ->leftjoin('groups', 'groups.id', '=', 'group_members.member_id')
            ->where('groups.status', 'ACTIVE')
            ->chunk(1000, function ($members) use (&$users) {
                $groupIds = $members->pluck('member_id')->toArray();
                $users = $this->addActiveAssignedGroupMembers($groupIds, $users);
            });

        return $users;
    }

    /**
     * Check if the user belongs to the group
     */
    private function doesUserBelongsGroup($userId, $groupId)
    {
        $isMember = GroupMember::where('group_id', $groupId)
            ->where('member_type', User::class)
            ->where('member_id', $userId)
            ->first();
        if ($isMember) {
            return true;
        } else {
            $groupMembers = GroupMember::where('group_id', $groupId)
                ->where('member_type', Group::class)
                ->get();
            foreach ($groupMembers as $groupMember) {
                $belongs = $this->doesUserBelongsGroup($userId, $groupMember->member_id);
                if ($belongs) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get a list of the process start events.
     *
     * @return array
     */
    public function getStartEvents($filterWithPermissions = false, $filterWithoutAssignments = false)
    {
        $user = Auth::user();
        // Load Process Start Events
        if (!isset($this->start_events)) {
            $this->start_events = $this->getUpdatedStartEvents();
        }
        // If user is administrator heshe can access all the start events
        if (!$filterWithPermissions || $user->is_administrator) {
            return $this->start_events;
        }
        // If user have edit and view permissions and filter without assignments
        if ($filterWithoutAssignments && $user->can('view-processes') && $user->can('edit-processes')) {
            return $this->start_events;
        }

        // Filter the start events assigned to the user
        $response = [];
        foreach ($this->start_events as $startEvent) {
            if (isset($startEvent['assignment']) && $startEvent['assignment'] === 'user' && isset($startEvent['assignedUsers'])) {
                $users = explode(',', ($startEvent['assignedUsers'] ?? ''));
                $access = in_array($user->id, $users);
            } elseif (isset($startEvent['assignment']) && $startEvent['assignment'] === 'group' && isset($startEvent['assignedGroups'])) {
                $access = false;
                foreach (explode(',', ($startEvent['assignedGroups'] ?? '')) as $groupId) {
                    $access = $this->doesUserBelongsGroup($user->id, $groupId);
                    if ($access) {
                        break;
                    }
                }
            } elseif (isset($startEvent['assignment']) && $startEvent['assignment'] === 'process_manager') {
                $access = $this->manager && $this->manager->id && $this->manager->id === $user->id;
            } else {
                $access = false;
            }
            if ($access) {
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
     * Create or update custom routes for webentry
     *
     * @return void
     */
    public function manageCustomRoutes()
    {
        foreach ($this->start_events as $startEvent) {
            $webEntryProperties = (isset($startEvent['config']) && isset(json_decode($startEvent['config'])->web_entry) ? json_decode($startEvent['config'])->web_entry : null);

            if ($webEntryProperties && isset($webEntryProperties->webentryRouteConfig)) {
                switch ($webEntryProperties->webentryRouteConfig->urlType) {
                    case 'standard-url':
                        $this->deleteUnusedCustomRoutes(
                            $webEntryProperties->webentryRouteConfig->firstUrlSegment,
                            $webEntryProperties->webentryRouteConfig->processId,
                            $webEntryProperties->webentryRouteConfig->nodeId
                        );
                        break;

                    default:
                        $this->manageWebentryRoute($webEntryProperties);
                        break;
                }
            }
        }
    }

    private function manageWebentryRoute($webEntryProperties)
    {
        if ($webEntryProperties->webentryRouteConfig->firstUrlSegment !== '') {
            $webentryRouteConfig = $webEntryProperties->webentryRouteConfig;
            try {
                WebentryRoute::updateOrCreate(
                    [
                        'process_id' => $this->id,
                        'node_id' => $webentryRouteConfig->nodeId,
                    ],
                    [
                        'first_segment' => $webentryRouteConfig->firstUrlSegment,
                        'params' => $webentryRouteConfig->parameters,
                    ]
                );
            } catch (Exception $e) {
                \Log::info('*** Error: ' . $e->getMessage());
            }
        }
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
    private function getStartEventPermissions(User $user)
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
     * @return ProcessEvents
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
     * Get the associated webEntryRoute
     */
    public function webentryRoute()
    {
        return $this->hasOne(WebentryRoute::class);
    }

    /**
     * Get the associated launchpad
     */
    public function launchpad()
    {
        return $this->hasOne(ProcessLaunchpad::class, 'process_id', 'id');
    }

    /**
     * Assignments of the process.
     *
     * @return HasMany
     */
    public function assignments()
    {
        return $this->hasMany(ProcessTaskAssignment::class);
    }

    /**
     * Return true if the process has an Timer Start Event
     *
     * @return bool
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
     * @param $token
     * @param $activity
     *
     * @return int|null $user_id
     * @throws TaskDoesNotHaveRequesterException
     */
    private function getRequester($activity, $token)
    {
        $processRequest = $token->getInstance();

        $validateUserId = $activity instanceof Activity;

        if ($validateUserId && !$processRequest->user_id) {
            throw new TaskDoesNotHaveRequesterException();
        }

        return $processRequest->user_id;
    }

    /**
     * Check the BPMN and convert not supported or extended features
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
        $duplicated = self::where('name', 'like', $name . '%')
            ->orderBy(DB::raw('LENGTH(name), name'))
            ->get();
        if ($duplicated->count()) {
            $duplicated = $duplicated->last();
            $number = intval(substr($duplicated->name, strlen($name))) + 1;
            $name = $name . ' (' . $number . ')';
        }
        $process = new self([
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
     * Check if process is valid for execution
     *
     * @return bool
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

    /**
     * Get the unique Conditional Start Events.
     *
     * @return array
     */
    public function getUpdatedConditionalStartEvents()
    {
        return collect($this->start_events)->filter(function ($startEvent) {
            return collect($startEvent['eventDefinitions'])->search(function ($event) {
                return $event['$type'] === 'conditionalEventDefinition';
            }) !== false;
        })->pluck('id');
    }

    /**
     * Check if the process is properly defined to run.
     *
     * @return bool
     */
    public function validateBpmnDefinition($addWarnings = false, &$warning = [])
    {
        $warnings = [];
        try {
            $definitions = $this->getDefinitions();
            $warnings = $this->validateSchema($definitions);
            $engine = app(BpmnEngine::class, ['definitions' => $definitions]);
            $processes = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process');
            foreach ($processes as $process) {
                $process->getBpmnElementInstance()->getTransitions($engine->getRepository());
            }
            $callActivities = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'callActivity');
            foreach ($callActivities as $callActivity) {
                $this->validateCallActivity($callActivity->getBpmnElementInstance());
            }
        } catch (Throwable $exception) {
            \Log::error($exception);
            $warning = [
                'title' => __('Invalid process'),
                'text' => $exception->getMessage(),
            ];
            if ($addWarnings) {
                $warnings[] = $warning;
                $this->warnings = $warnings;
            }

            return false;
        }

        return true;
    }

    /**
     * Validates a call activity configuration.
     *
     * @param CallActivity $callActivity
     * @throw \Exception if the call activity is not properly configured.
     */
    private function validateCallActivity(CallActivity $callActivity)
    {
        // Get process version without evaluating alternative
        $targetProcess = $callActivity->getCalledElement([], false);
        $config = json_decode($callActivity->getProperty('config'), true);
        $startId = is_array($config) && isset($config['startEvent']) ? $config['startEvent'] : null;
        if ($startId) {
            $element = $targetProcess->getOwnerDocument()->findElementById($startId);
            if (!$element) {
                throw new Exception(__('The start event with id ":node_id" does not exist', ['node_id' => $startId]));
            }
            $startEvent = $element->getBpmnElementInstance();
            if (!($startEvent instanceof StartEventInterface)) {
                throw new Exception(__('The start event of the call activity is not a start event'));
            }
            $eventDefinitions = $startEvent->getEventDefinitions();
            if ($eventDefinitions && $eventDefinitions->count() > 0) {
                throw new Exception(__('The start event of the call activity is not empty'));
            }
            $config = json_decode($startEvent->getProperty('config'), true);
            if ($config && isset($config['web_entry'])) {
                throw new Exception(__('The start event of the call activity can not be a web entry'));
            }
        }
    }

    /**
     * Validates the Bpmn content of the process.
     *
     * @param BpmnDocument $request
     * @return array
     */
    private function validateSchema(BpmnDocument $document)
    {
        $schemaErrors = [];
        try {
            $document->validateBPMNSchema(public_path('definitions/ProcessMaker.xsd'));
        } catch (Exception $e) {
            $schemaErrors = $document->getValidationErrors();
            $schemaErrors[] = $e->getMessage();
        }
        $rulesValidation = new BPMNValidation;
        if (!$rulesValidation->passes('document', $document)) {
            $errors = $rulesValidation->errors('document', $document)->getMessages();
            $schemaErrors[] = [
                'title' => 'BPMN Validation failed',
                'text' => __('Some bpmn elements do not comply with the validation'),
                'errors' => $errors,
            ];
        }

        return $schemaErrors;
    }

    private function deleteUnusedCustomRoutes($url, $processId, $nodeId)
    {
        // Delete unused custom routes
        $customRoute = WebentryRoute::where('process_id', $processId)->where('node_id', $nodeId)->first();
        if ($customRoute) {
            $customRoute->delete();
        }
    }

    /**
     * PMQL value alias for fulltext field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasFullText($value, $expression)
    {
        return function ($query) use ($value) {
            $this->scopeFilter($query, $value);
        };
    }

    /**
     * PMQL value alias for owner field
     *
     * @param string $value
     *
     * @return callable
     */
    private function valueAliasOwner($value, $expression)
    {
        $user = User::where('username', $value)->get()->first();

        if ($user) {
            return function ($query) use ($user, $expression) {
                $query->where('processes.user_id', $expression->operator, $user->id);
            };
        } else {
            throw new PmqlMethodException('owner', 'The specified owner username does not exist.');
        }
    }

    /**
     * PMQL value alias for process field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasName($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $processes = self::where('name', $expression->operator, $value)->get();
            $query->whereIn('processes.id', $processes->pluck('id'));
        };
    }

    /**
     * PMQL value alias for process field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasProcess($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $processes = self::where('name', $expression->operator, $value)->get();
            $query->whereIn('processes.id', $processes->pluck('id'));
        };
    }

    /**
     * PMQL value alias for status field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasStatus($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $processes = self::where('status', $expression->operator, $value)->get();
            $query->whereIn('processes.id', $processes->pluck('id'));
        };
    }

    /**
     * PMQL value alias for id field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasId($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $processes = self::where('id', $expression->operator, $value)->get();
            $query->whereIn('processes.id', $processes->pluck('id'));
        };
    }

    /**
     * PMQL value alias for category field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasCategory($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $categoryAssignment = DB::table('category_assignments')->leftJoin('process_categories', function ($join) {
                $join->on('process_categories.id', '=', 'category_assignments.category_id');
                $join->where('category_assignments.category_type', '=', ProcessCategory::class);
                $join->where('category_assignments.assignable_type', '=', self::class);
            })
                ->where('name', $expression->operator, $value);
            $query->whereIn('processes.id', $categoryAssignment->pluck('assignable_id'));
        };
    }

    /**
     * Filter settings with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function scopeFilter($query, $filterStr)
    {
        $filter = '%' . mb_strtolower($filterStr) . '%';
        $query->where(function ($query) use ($filter, $filterStr) {
            $query->where('processes.name', 'like', $filter)
                 ->orWhere('processes.description', 'like', $filter)
                 ->orWhere('processes.status', '=', $filterStr)
                 ->orWhereHas('user', function ($query) use ($filter) {
                     $query->where('firstname', 'like', $filter)
                         ->orWhere('lastname', 'like', $filter);
                 })
                 ->orWhereIn('processes.id', function ($qry) use ($filter) {
                     $qry->select('assignable_id')
                         ->from('category_assignments')
                         ->leftJoin('process_categories', function ($join) {
                             $join->on('process_categories.id', '=', 'category_assignments.category_id');
                             $join->where('category_assignments.category_type', '=', ProcessCategory::class);
                             $join->where('category_assignments.assignable_type', '=', self::class);
                         })
                         ->where('process_categories.name', 'like', $filter);
                 });
        });

        return $query;
    }

    /**
     * Define the "belongsTo" relationship between the Process model and the PmBlock model.
     */
    public function pmBlock()
    {
        return $this->belongsTo('ProcessMaker\Package\PackagePmBlocks\Models\PmBlock', 'id', 'editing_process_id');
    }

    /**
     * This function copies original image and converts into a thumbnail
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(1024)
              ->height(480)
              ->sharpen(10);
    }

    /**
     * Returns true if the model has alternatives.
     *
     * @return false
     */
    public function hasAlternative()
    {
        return true;
    }

    public function scopeOrderByRecentRequests($query)
    {
        return $query->orderByDesc(
            ProcessRequest::select('id')
                // User has participated
                ->whereHas('tokens', function ($q) {
                    $q->where('user_id', Auth::user()->id);
                })
                ->whereColumn('process_id', 'processes.id')
                ->orderByDesc('id') // using ID because created_at is not indexed
                ->limit(1)
        );
    }
}
