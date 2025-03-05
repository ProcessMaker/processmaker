<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Scout\Searchable;
use Log;
use ProcessMaker\Casts\MillisecondsToDateCast;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Events\ActivityReassignment;
use ProcessMaker\Facades\WorkflowUserManager;
use ProcessMaker\Nayra\Bpmn\TokenTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\MultiInstanceLoopCharacteristicsInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Managers\WorkflowManagerDefault;
use ProcessMaker\Notifications\ActivityActivatedNotification;
use ProcessMaker\Notifications\TaskReassignmentNotification;
use ProcessMaker\Query\Expression;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasUuids;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;
use Throwable;

/**
 * ProcessRequestToken is used to store the state of a token of the
 * Nayra engine
 *
 * @property string $id
 * @property string $process_request_id
 * @property string $user_id
 * @property string $element_id
 * @property string $element_type
 * @property string $status
 * @property Carbon $completed_at
 * @property Carbon $due_at
 * @property Carbon $initiated_at
 * @property Carbon $riskchanges_at
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property Carbon $created_at_ms
 * @property Carbon $completed_at_ms
 * @property ProcessRequest $processRequest
 *
 * @OA\Schema(
 *   schema="processRequestTokenEditable",
 *   @OA\Property(property="user_id", type="string", format="id"),
 *   @OA\Property(property="status", type="string"),
 *   @OA\Property(property="due_at", type="string", format="date-time"),
 *   @OA\Property(property="initiated_at", type="string", format="date-time"),
 *   @OA\Property(property="riskchanges_at", type="string", format="date-time"),
 *   @OA\Property(property="subprocess_start_event_id", type="string"),
 *   @OA\Property(property="data", type="object"),
 * ),
 * @OA\Schema(
 *   schema="processRequestToken",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/processRequestTokenEditable"),
 *       @OA\Schema(
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="process_id", type="string", format="id"),
 *          @OA\Property(property="process_request_id", type="string", format="id"),
 *          @OA\Property(property="element_id", type="string", format="id"),
 *          @OA\Property(property="element_type", type="string", format="id"),
 *          @OA\Property(property="element_index", type="string"),
 *          @OA\Property(property="element_name", type="string"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          @OA\Property(property="initiated_at", type="string", format="date-time"),
 *          @OA\Property(property="advanceStatus", type="string"),
 *          @OA\Property(property="due_notified", type="integer"),
 *          @OA\Property(property="user", type="object", @OA\Schema(ref="#/components/schemas/users")),
 *          @OA\Property(property="process", type="object", @OA\Schema(ref="#/components/schemas/Process")),
 *          @OA\Property(property="process_request", type="object", @OA\Schema(ref="#/components/schemas/processRequest")),
 *       )
 *   }
 * )
 *
 * @method ProcessRequest getInstance()
 */
class ProcessRequestToken extends ProcessMakerModel implements TokenInterface
{
    use ExtendedPMQL;
    use HasUuids;
    use HideSystemResources;
    use Searchable;
    use SerializeToIso8601;
    use TokenTrait;

    protected $connection = 'processmaker';

    /**
     * Attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'uuid',
        'updated_at',
        'created_at',
        'data',
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
        'data',
    ];

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $ids = [
        'process_id',
        'process_request_id',
        'user_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'advanceStatus',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
        'initiated_at' => 'datetime',
        'riskchanges_at' => 'datetime',
        'data' => 'array',
        'self_service_groups' => 'array',
        'token_properties' => 'array',
        'is_priority' => 'boolean',
        'is_actionbyemail' => 'boolean',
        'created_at_ms' => MillisecondsToDateCast::class,
        'completed_at_ms' => MillisecondsToDateCast::class,
        'is_emailsent' => 'boolean',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $dataToInclude = $this->data;
        unset($dataToInclude['_request']);
        unset($dataToInclude['_user']);

        return [
            'id' => $this->id,
            'element_name' => $this->element_name,
            'request' => isset($this->processRequest->name) ? $this->processRequest->name : '',
            'data' => json_encode($dataToInclude),
        ];
    }

    /**
     * Determine whether the item should be indexed.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        $setting = Setting::byKey('indexed-search');
        if ($setting && $setting->config['enabled'] === true) {
            return in_array($this->element_type, ['task', 'userTask']);
        } else {
            return false;
        }
    }

    /**
     * Boot application as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument = [])
    {
        parent::__construct($argument);
        $this->bootElement([]);
    }

    /**
     * Notification settings of the process.
     *
     * @param string $entity
     * @param string $notificationType
     *
     * @return array
     */
    public function getNotifiables($notificationType)
    {
        $userIds = collect([]);

        $process = $this->process()->first();

        $notifiableTypes = $process->notification_settings()
                                   ->where('notification_type', $notificationType)
                                   ->where('element_id', $this->element_id)
                                   ->get()->pluck('notifiable_type');

        foreach ($notifiableTypes as $notifiableType) {
            $userIds = $userIds->merge($this->getNotifiableUserIds($notifiableType));
        }

        $userIds = $userIds->unique();

        $notifiables = $notifiableTypes->implode(', ');
        $users = $userIds->implode(', ');
        Log::debug("Sending task {$notificationType} notification to {$notifiables} (users: {$users})");

        return User::whereIn('id', $userIds)->get();
    }

    public function getNotifiableUserIds($notifiableType)
    {
        switch ($notifiableType) {
            case 'requester':
                return collect([$this->processRequest->user_id]);
                break;
            case 'assignee':
                return collect([$this->user_id]);
                break;
            case 'participants':
                return $this->processRequest->participants()->get()->pluck('id');
                break;
            case 'manager':
                $process = $this->process()->first();

                return collect([$process?->manager_id]);
                break;
            default:
                return collect([]);
        }
    }

    /**
     * Get the process to which this version points to.
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    /**
     * Get the request of the token.
     */
    public function processRequest()
    {
        return $this->belongsTo(ProcessRequest::class, 'process_request_id');
    }

    /**
     * Get the creator/author of this request.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the creator/author of this request.
     */
    public function assignableUsers()
    {
        $query = $this->newQuery()->where('id', $this->getKey());

        return new TokenAssignableUsers($query, $this);
    }

    /**
     * Scope to filter by case_number through the processRequest relationship
     */
    public function scopeFilterByCaseNumber($query, $request)
    {
        $caseNumber = $request->input('case_number');

        return $query->whereHas('processRequest', function ($query) use ($caseNumber) {
            $query->where('case_number', $caseNumber);
        });
    }

    /**
     * Scope to filter by status
     */
    public function scopeFilterByStatus($query, $request)
    {
        $status = $request->input('status', 'ACTIVE');

        return $query->where('status', $status);
    }

    /**
     * Scope get process information
     */
    public function scopeGetProcess($query)
    {
        return $query->with(['process' => function ($query) {
            $query->select('id', 'name');
        }]);
    }

    /**
     * Scope get user information
     */
    public function scopeGetUser($query)
    {
        return $query->with(['user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', 'username', 'avatar');
        }]);
    }

    /**
     * Scope apply order
     */
    public function scopeApplyOrdering($query, $request)
    {
        $orderBy = $request->input('order_by', 'due_at');
        $orderDirection = $request->input('order_direction', 'asc');

        return $query->orderBy($orderBy, $orderDirection);
    }

    /**
     * Scope apply pagination
     */
    public function scopeApplyPagination($query, $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        return $query->paginate($perPage);
    }

    /**
     * Returns either the owner element or its properties
     *
     * @param asObject Boolean flag that determines whether the function should return the element directly or its
     * properties as an object.
     *
     * @return If the parameter is true, the function will return the object. If is false, the
     * function will return the properties of the object.
     */
    private function getDefinitionFromOwner($asObject)
    {
        $element = $this->getOwnerElement();

        return $asObject ? $element : $element->getProperties();
    }

    /**
     * Retrieves a specific element's BPMN definition from a request object and returns either the element
     * itself or its properties.
     *
     * @param asObject Boolean flag that determines whether the function should return the BPMN element instance
     * as an object or just its properties.
     *
     * @return If `asObject` is false, the properties of the BPMN element instance are returned.
     */
    private function getDefinitionFromRequest($asObject)
    {
        $request = $this->processRequest ?: $this->getInstance();

        if (!$request) {
            return [];
        }

        $process = $request->processVersion ?: $request->process;
        $definitions = $process->getDefinitions();
        $element = $definitions->findElementById($this->element_id);

        if (!$element) {
            return [];
        }

        return $asObject ? $element->getBpmnElementInstance() : $element->getBpmnElementInstance()->getProperties();
    }

    /**
     * Get the BPMN definition of the element where the token is.
     *
     * @return array|\ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface
     */
    public function getDefinition($asObject = false, $par = null)
    {
        if ($this->getOwner() && $this->getOwnerElement()) {
            return $this->getDefinitionFromOwner($asObject);
        }

        return $this->getDefinitionFromRequest($asObject);
    }

    /**
     * Get the BPMN element node where the token is currently located.
     *
     * @return \ProcessMaker\Nayra\Storage\BpmnElement
     */
    public function getBpmnDefinition()
    {
        /** @var ProcessRequest $request */
        $request = $this->processRequest ?: $this->getInstance();
        $definitions = $request->getVersionDefinitions();

        return $definitions->findElementById($this->element_id);
    }

    /**
     * Get the form assigned to the task.
     */
    public function getScreen(): ?Screen
    {
        $definition = $this->getDefinition();
        $screenRef = $definition['screenRef'] ?? null;
        $screen = Screen::find($screenRef);

        if ($screen === null) {
            // Attempt to retrieve the localName property from the bpmnDefinition object.
            // It uses a try-catch block to handle any exceptions that might occur, for example in test environments.
            try {
                $localName = $this->getBpmnDefinition()->localName;
            } catch (Throwable $t) {
                $localName = null;
            }
            $isManualTask = $localName === 'manualTask';
            $defaultScreen = $isManualTask ? 'default-display-screen' : 'default-form-screen';

            $screen = Screen::getScreenByKey($defaultScreen);

            if (array_key_exists('implementation', $definition) && $definition['implementation'] === 'package-ai/processmaker-ai-assistant') {
                $defaultScreen = 'default-ai-form-screen';
                $screen = Screen::getScreenByKey($defaultScreen);
            }
        }

        return $screen;
    }

    /**
     * Get the screen assigned to the task at the time the request started
     *
     * @return ScreenInterface
     */
    public function getScreenVersion()
    {
        $screen = $this->getScreen();

        if ($screen === null) {
            return null;
        }

        return $screen->versionFor($this->processRequest);
    }

    /**
     * Get the ID of the task screen (if set) and any nested screens
     *
     * @return int[] Array of screen IDs
     */
    public function getScreenAndNestedIds()
    {
        $taskScreen = $this->getScreen();
        $interstitialScreen = $this->getInterstitial()['interstitial_screen'];
        $screenIds = [];

        if (!$taskScreen && !$interstitialScreen) {
            $screenIds = [];
        }

        if ($taskScreen) {
            $screenIds = $taskScreen->nestedScreenIds($this->processRequest);
            $screenIds[] = $taskScreen->id;
        }

        if ($interstitialScreen && $interstitialScreen->config) {
            if (isset($screenIds) && count($screenIds)) {
                $screenIds = array_merge($interstitialScreen->nestedScreenIds($this->processRequest), $screenIds);
            } else {
                $screenIds = $interstitialScreen->nestedScreenIds($this->processRequest);
            }
            $screenIds[] = $interstitialScreen->id;
        }

        return $screenIds;
    }

    /**
     * Get the script assigned to the task.
     *
     * @return Script
     */
    public function getScript()
    {
        $definition = $this->getDefinition();

        return empty($definition['scriptRef']) ? null : Script::find($definition['scriptRef']);
    }

    /**
     * Get the script assigned to the task at the time the request started
     *
     * @return ScriptInterface
     */
    public function getScriptVersion()
    {
        $script = $this->getScript();

        if (!$script) {
            return null;
        }

        return $script->versionFor($this->processRequest);
    }

    /**
     * Returns the state of the advance of the request token (open, completed, overdue)
     *
     * @return string
     */
    public function getAdvanceStatusAttribute()
    {
        $result = 'open';

        $isOverdue = Carbon::now()->gte(Carbon::parse($this->due_at));

        if ($isOverdue && $this->status === 'ACTIVE') {
            $result = 'overdue';
        }

        if (!$isOverdue && $this->status === 'ACTIVE') {
            $result = 'open';
        }

        if ($this->status === 'CLOSED') {
            $result = 'completed';
        }

        if ($this->status === 'TRIGGERED') {
            $result = 'triggered';
        }

        return $result;
    }

    /**
     * Check if this task can be escalated to the manager by the assignee
     *
     * @return true if the task can be escalated
     * @throws AuthorizationException if it can not
     */
    public function authorizeAssigneeEscalateToManager()
    {
        $definitions = $this->getDefinition();
        if (isset($definitions['config'])) {
            $config = json_decode($definitions['config'], true);
            if (Arr::get($config, 'assigneeManagerEscalation', false)) {
                return true;
            }
        }

        throw new AuthorizationException('Not authorized to escalate to manager');
    }

    /**
     * Scheduled task for this token
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scheduledTasks()
    {
        return $this->hasMany(ScheduledTask::class, 'process_request_token_id');
    }

    public function draft()
    {
        return $this->hasOne(TaskDraft::class, 'task_id');
    }

    /**
     * Get the sub-process request associated to the token.
     */
    public function subProcessRequest()
    {
        return $this->belongsTo(ProcessRequest::class, 'subprocess_request_id');
    }

    /**
     * Scope overdue
     *
     * @var Builder
     */
    public function scopeOverdue($query, $overdue = '')
    {
        if (!empty($overdue)) {
            return $query->where('due_at', '<', Carbon::now());
        }
    }

    /**
     * Filter tokens with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function scopeFilter($query, $filter)
    {
        $setting = Setting::byKey('indexed-search');
        if ($setting && $setting->config['enabled'] === true) {
            if (is_numeric($filter)) {
                $query->whereIn('id', [$filter]);
            } else {
                $matches = self::search($filter)->take(10000)->get()->pluck('id');
                $query->whereIn('id', $matches);
            }
        } else {
            $filter = '%' . mb_strtolower($filter) . '%';
            $query->where(function ($query) use ($filter) {
                $query->where(DB::raw('LOWER(element_name)'), 'like', $filter)
                    ->orWhere(DB::raw('LOWER(data)'), 'like', $filter)
                    ->orWhere(DB::raw('LOWER(status)'), 'like', $filter)
                    ->orWhere('id', 'like', $filter)
                    ->orWhere('created_at', 'like', $filter)
                    ->orWhere('due_at', 'like', $filter)
                    ->orWhere('updated_at', 'like', $filter)
                    ->orWhereHas('processRequest', function ($query) use ($filter) {
                        $query->where(DB::raw('LOWER(name)'), 'like', $filter);
                    })
                    ->orWhereHas('processRequest', function ($query) use ($filter) {
                        $query->where(DB::raw('LOWER(data)'), 'like', $filter);
                    })
                    ->orWhereHas('process', function ($query) use ($filter) {
                        $query->where(DB::raw('LOWER(name)'), 'like', $filter);
                    });
            });
        }

        return $query;
    }

    /**
     * PMQL field alias (started = created_at)
     *
     * @return string
     */
    public function fieldAliasStarted()
    {
        return 'created_at';
    }

    /**
     * PMQL field alias (created = created_at)
     *
     * @return string
     */
    public function fieldAliasCreated()
    {
        return 'created_at';
    }

    /**
     * PMQL field alias (modified = updated_at)
     *
     * @return string
     */
    public function fieldAliasModified()
    {
        return 'updated_at';
    }

    /**
     * PMQL field alias (due = due_at)
     *
     * @return string
     */
    public function fieldAliasDue()
    {
        return 'due_at';
    }

    /**
     * PMQL field alias (completed = completed_at)
     *
     * @return string
     */
    public function fieldAliasCompleted()
    {
        return 'completed_at';
    }

    public function fieldAliasUser_Id()
    {
        return 'process_request_tokens.user_id';
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
     * PMQL value alias for status field
     *
     * @param string $value
     * @param ProcessMaker\Query\Expression $expression
     *
     * @return callable
     */
    public function valueAliasStatus($value, $expression, $callback = null, User $user = null)
    {
        $statusMap = [
            'in progress' => ['ACTIVE'],
            'completed' => ['CLOSED', 'COMPLETED'],
        ];

        $value = mb_strtolower($value);

        return function ($query) use ($value, $statusMap, $expression, $user) {
            if ($value === 'self service') {
                if (!$user) {
                    $user = auth()->user();
                }

                if ($user) {
                    $taskIds = $user->availableSelfServiceTaskIds();
                    $query->whereIn('id', $taskIds);
                } else {
                    $query->where('is_self_service', 1);
                }
            } elseif (array_key_exists($value, $statusMap)) {
                $query->where('is_self_service', 0);
                if ($expression->operator == '=') {
                    $query->whereIn('process_request_tokens.status', $statusMap[$value]);
                } elseif ($expression->operator == '!=') {
                    $query->whereNotIn('process_request_tokens.status', $statusMap[$value]);
                }
            } else {
                $query->where('status', $expression->operator, $value)
                    ->where('is_self_service', 0);
            }
        };
    }

    /**
     * PMQL value alias for request field
     *
     * @param string $value
     * @param ProcessMaker\Query\Expression $expression
     *
     * @return callable
     */
    public function valueAliasRequest($value, $expression)
    {
        return function ($query) use ($expression, $value) {
            $processRequests = ProcessRequest::where('name', $expression->operator, $value)->get();
            $query->whereIn('process_request_tokens.process_request_id', $processRequests->pluck('id'));
        };
    }

    /**
     * PMQL value alias for task field
     *
     * @param string $value
     * @param ProcessMaker\Query\Expression $expression
     *
     * @return callable
     */
    public function valueAliasTask($value, $expression)
    {
        return function ($query) use ($expression, $value) {
            $query->where('process_request_tokens.element_name', $expression->operator, $value);
        };
    }

    /**
     * PMQL value alias for the case number field related to process request
     *
     * @param string value
     * @param ProcessMaker\Query\Expression expression
     *
     * @return callable
     */
    public function valueAliasCase_Number(string $value, Expression $expression): callable
    {
        return function ($query) use ($expression, $value) {
            $query->whereHas('processRequest', function ($query) use ($expression, $value) {
                return $query->where('case_number', $expression->operator, $value);
            });
        };
    }

    /**
     * PMQL value alias for the case title field related to process request
     *
     * @param string value
     * @param ProcessMaker\Query\Expression expression
     *
     * @return callable
     */
    public function valueAliasCase_Title(string $value, Expression $expression): callable
    {
        return function ($query) use ($expression, $value) {
            $query->whereHas('processRequest', function ($query) use ($expression, $value) {
                $query->where('case_title', $expression->operator, $value);
            });
        };
    }

    /**
     * PMQL value alias for the process name field related to process request
     *
     * @param string value
     * @param ProcessMaker\Query\Expression expression
     *
     * @return callable
     */
    public function valueAliasProcess_Name(string $value, Expression $expression): callable
    {
        return function ($query) use ($expression, $value) {
            $query->whereHas('processRequest', function ($query) use ($expression, $value) {
                $query->where('name', $expression->operator, $value);
            });
        };
    }

    /**
     * PMQL value alias for assignee field by fullname.
     * @param string $value
     * @return callable
     */
    public function valueAliasAssigneeByFullName($value, $expression)
    {
        return function ($query) use ($expression, $value) {
            $query->whereHas('user', function ($query) use ($expression, $value) {
                $query->whereRaw("CONCAT(firstname, ' ', lastname) " . $expression->operator . ' ?', [$value]);
            });
        };
    }

    /**
     * PMQL wildcard for process request & data fields
     *
     * @param string $value
     * @param ProcessMaker\Query\Expression $expression
     *
     * @return mixed
     */
    public function fieldWildcard($value, $expression)
    {
        if (is_object($expression->field->field())) {
            return function ($query) use ($expression, $value) {
                $field = $expression->field->toEloquent();
                $operator = $expression->operator;

                $requestIds = ProcessRequest::where($field, $operator, $value)->pluck('id');
                $query->whereIn('process_request_id', $requestIds);
            };
        } else {
            if (stripos($expression->field->field(), 'data.') === 0) {
                $field = $expression->field->field();
                $operator = $expression->operator;
                if (is_string($value)) {
                    $value = '"' . $value . '"';
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                }

                $pmql = "{$field} {$operator} {$value}";

                return function ($query) use ($pmql) {
                    $requestIds = ProcessRequest::pmql($pmql)->pluck('id');
                    $query->whereIn('process_request_id', $requestIds);
                };
            }
        }
    }

    /**
     * Save version of the executable artifact (screen, script)
     */
    public function saveVersion()
    {
        $screenVersion = $this->getScreenVersion();
        $scriptVersion = $this->getScriptVersion();
        if ($screenVersion) {
            $this->version_id = $screenVersion->getKey();
            $this->version_type = ScreenVersion::class;
        } elseif ($scriptVersion) {
            $this->version_id = $scriptVersion->getKey();
            $this->version_type = ScriptVersion::class;
        }
    }

    /**
     * Get the assignment rule for the token.
     *
     * @return string
     */
    public function getAssignmentRule()
    {
        $activity = $this->getBpmnDefinition()->getBpmnElementInstance();
        $assignmentRules = $activity->getProperty('assignmentRules', null);

        $assignment = $activity->getProperty('assignment', null);

        if ($assignment !== 'rule_expression') {
            return $assignment;
        }

        // Below is for rule_expression only
        $instance = $this->getInstance();
        if (!$instance) {
            return $assignment;
        }

        $instanceData = $assignmentRules ? $instance->getDataStore()->getData() : null;
        if ($assignmentRules && $instanceData) {
            $list = json_decode($assignmentRules);
            $list = ($list === null) ? [] : $list;
            foreach ($list as $item) {
                $formalExp = new FormalExpression();
                $formalExp->setLanguage('FEEL');
                $formalExp->setBody($item->expression);
                $eval = $formalExp($instanceData);
                if ($eval) {
                    return $item->type;
                }
            }
        }

        return $assignment;
    }

    /**
     * Returns if the token has the self service option activated
     */
    public function getSelfServiceAttribute()
    {
        $activity = $this->getBpmnDefinition()->getBpmnElementInstance();

        $config = json_decode($activity->getProperty('config'));
        if (empty($config)) {
            return false;
        }

        return (property_exists($config, 'selfService')) ? $config->selfService : false;
    }

    /**
     * Get Interstitial properties
     *
     * @return array
     */
    public function getInterstitial()
    {
        $definition = $this->getDefinition();
        $interstitialScreen = new Screen();
        $allowInterstitial = false;
        if (array_key_exists('allowInterstitial', $definition)) {
            $allowInterstitial = (bool) json_decode($definition['allowInterstitial']);
            if (array_key_exists('interstitialScreenRef', $definition) && $definition['interstitialScreenRef']) {
                if (is_numeric($definition['interstitialScreenRef'])) {
                    $interstitialScreen = Screen::find($definition['interstitialScreenRef']);
                } else {
                    $interstitialScreen = Screen::where('key', $definition['interstitialScreenRef'])->first();
                }
            } else {
                $interstitialScreen = Screen::getScreenByKey('interstitial');
            }
        }

        return [
            'allow_interstitial' => $allowInterstitial,
            'interstitial_screen' => $interstitialScreen,
        ];
    }

    public function persistUserData($user)
    {
        if (!is_a($user, User::class)) {
            $user = User::find($user);
        }

        $userData = $user->attributesToArray();
        $data = $this->processRequest->data;
        $data['_user'] = $userData;

        $this->processRequest->data = $data;
        $this->processRequest->save();
    }

    /**
     * Log an error when executing the token
     *
     * @param Throwable $error
     * @param FlowElementInterface $bpmnElement
     */
    public function logError(Throwable $error, FlowElementInterface $bpmnElement)
    {
        $this->getInstance()->logError($error, $bpmnElement);
    }

    public function updateTokenProperties()
    {
        $allowed = ['conditionals', 'loopCharacteristics', 'data', 'error'];
        $this->token_properties = array_filter(
            $this->getProperties(),
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    public function loadTokenProperties()
    {
        $tokenInfo = [
            'id' => $this->getKey(),
            'status' => $this->status,
            'index' => $this->element_index,
            'element_ref' => $this->element_id,
        ];
        $this->setProperties(array_merge($this->token_properties ?: [], $tokenInfo));
    }

    public function loadTokenInstance()
    {
        $instance = $this->processRequest->loadProcessRequestInstance();
        $this->setInstance($instance);
        $this->loadTokenProperties();

        return $this;
    }

    public function saveToken()
    {
        $activity = $this->getOwnerElement();
        $token = $this;
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->element_type = $activity->getBpmnElement()->localName;
        $token->element_name = $activity->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->saveOrFail();
        $token->setId($token->getKey());
    }

    /**
     * Escalate task to manager
     *
     * @return int
     */
    public function escalateToManager()
    {
        if (app()->bound('workflow.UserManager')) {
            $escalateTo = WorkflowUserManager::escalateToManager($this, $this->user_id);
            $this->user_id = $escalateTo;

            return $escalateTo;
        }

        return $this->user_id;
    }

    /**
     * Reassign task to $userId
     *
     * @param string $userId
     *
     * @return self
     */
    public function reassignTo($userId)
    {
        if ($userId === '#manager') {
            $this->escalateToManager();

            return $this;
        }
        //This depends on the `package-advanced-user-manager` package.
        $assignmentProcess = Process::where('name', Process::ASSIGNMENT_PROCESS)->first();
        if ($assignmentProcess) {
            $res = (new WorkflowManagerDefault)->runProcess($assignmentProcess, 'assign', [
                'task_id' => $this->id,
                'user_id' => $userId,
                'process_id' => $this->process_id,
                'request_id' => $this->process_request_id,
            ]);
            $this->user_id = $res['assign_to'];
        }

        return $this;
    }

    /**
     * Returns True is the tokens belongs to a MultiInstance Task
     *
     * @return bool
     */
    public function isMultiInstance()
    {
        $definition = $this->getDefinition(true);
        if ($definition instanceof ActivityInterface) {
            $loop = $definition->getLoopCharacteristics();

            return $loop && $loop->isExecutable() && $loop instanceof MultiInstanceLoopCharacteristicsInterface;
        }

        return false;
    }

    public function getLoopContext()
    {
        $isMultiInstance = isset($this->token_properties['data']);
        if (!$isMultiInstance) {
            return '';
        }
        $loopData = $this->token_properties['data'] ?? [];

        $index = null;
        if (array_key_exists('loopCounter', $loopData)) {
            $index = $loopData['loopCounter'];
        }
        $definition = $this->getDefinition(true);
        if (!$definition instanceof ActivityInterface) {
            return '';
        }
        $loop = $definition->getLoopCharacteristics();
        if ($loop && $loop->isExecutable() && $loop instanceof MultiInstanceLoopCharacteristicsInterface) {
            $output = $loop->getLoopDataOutput();
            $variable = $output ? $output->getName() : '';
        } else {
            return '';
        }

        return $index !== null ? $variable . '.' . $index : $variable;
    }

    /**
     * Get a config parameter from the bpmn element definition.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getConfigParam($key, $default = null)
    {
        $definition = $this->getDefinition(true);
        $config = json_decode($definition->getProperty('config', '{}'), true);
        if (!empty($config) && \is_array($config)) {
            return Arr::get($config, $key, $default);
        }

        return $default;
    }

    /**
     * Send notifications when the task activates
     *
     * @return void
     */
    public function sendActivityActivatedNotifications()
    {
        $notifiables = $this->getNotifiables('assigned');
        Notification::send($notifiables, new ActivityActivatedNotification($this));
    }

    /**
     * Reassign task
     *
     * @param mixed $userId
     * @param User $requestingUser
     * @return void
     */
    public function reassign($toUserId, User $requestingUser)
    {
        $sendActivityActivatedNotifications = false;
        $reassingAction = false;
        if ($this->is_self_service && $toUserId == $requestingUser->id && !$this->user_id) {
            // Claim task
            $this->is_self_service = 0;
            $this->user_id = $toUserId;
            $this->persistUserData($toUserId);
            $sendActivityActivatedNotifications = true;
        } elseif ($toUserId === '#manager') {
            // Reassign to manager
            $this->authorizeAssigneeEscalateToManager();
            $toUserId = $this->escalateToManager();
            $this->persistUserData($toUserId);
            $reassingAction = true;
        } else {
            // Validate if user can reassign
            Gate::forUser($requestingUser)->authorize('reassign', $this);
            // Reassign user
            $this->is_self_service = 0; //When reassigning the user, it is not necessary to claim the task.
            $this->reassignTo($toUserId);
            $this->persistUserData($toUserId);
            $reassingAction = true;
        }
        $this->save();

        if ($sendActivityActivatedNotifications) {
            $this->sendActivityActivatedNotifications();
        }
        // Register the Event
        if ($reassingAction) {
            ActivityReassignment::dispatch($this);
        }

        // Send a notification to the user
        $notification = new TaskReassignmentNotification($this);
        $this->user->notify($notification);
        if ($this->element_type === 'task') {
            event(new ActivityAssigned($this));
        }
    }

    /**
     * Determines the destination based on the type of element destination property
     *
     * @param elementDestinationType Used to determine the type of destination for an element.
     * @param elementDestinationProp Used to determine the properties of the destination for an element.
     *
     * @return array|null Returns the destination URL.
     */
    private function getElementDestination($elementDestinationType, $elementDestinationProp): ?array
    {
        $elementDestination = null;

        switch ($elementDestinationType) {
            case 'anotherProcess':
            case 'customDashboard':
            case 'externalURL':
                if (array_key_exists('value', $elementDestinationProp)) {
                    if (is_string($elementDestinationProp['value'])) {
                        $elementDestination = $elementDestinationProp['value'];
                    } else {
                        $elementDestination = $elementDestinationProp['value']['url'] ?? null;
                    }
                }
                break;
            case 'taskList':
                $elementDestination = route('tasks.index');
                break;
            case 'homepageDashboard':
                if (hasPackage('package-dynamic-ui')) {
                    $user = auth()->user();
                    $elementDestination = \ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::getHomePage($user);
                } else {
                    $elementDestination = route('home');
                }
                break;
            case 'processLaunchpad':
                $elementDestination = route('process.browser.index', [
                    'process' => $this->process_id,
                    'categorySelected' => -1,
                ]);
                break;
            case 'taskSource':
                $elementDestination = null;
                break;
            default:
                $elementDestination = null;
                break;
        }

        return [
            'type' => $elementDestinationType,
            'value' => $elementDestination,
        ];
    }

    /**
     * Determines the destination URL based on the element destination type specified in the definition.
     *
     * @return array|null
     */
    public function getElementDestinationAttribute(): ?array
    {
        $definition = $this->getDefinition();
        $elementDestinationProp = $definition['elementDestination'] ?? null;
        $elementDestinationType = null;

        try {
            $elementDestinationProp = json_decode($elementDestinationProp, true);
            if (is_array($elementDestinationProp) && array_key_exists('type', $elementDestinationProp)) {
                $elementDestinationType = $elementDestinationProp['type'];
            }
        } catch (Exception $e) {
            return null;
        }

        return $this->getElementDestination($elementDestinationType, $elementDestinationProp);
    }

    /**
     * Filter by user_id, includes assigned and self-service tasks
     *
     * @param mixed $query
     * @param mixed $userId ID of the user
     * @return mixed
     */
    public static function scopeWhereUserAssigned($query, $userId)
    {
        $userColumn = 'user_id';
        $query->where(function ($query) use ($userColumn, $userId) {
            $query->where($userColumn, $userId);
            $query->orWhere(function ($query) use ($userColumn, $userId) {
                $query->whereNull($userColumn);
                $query->where('process_request_tokens.is_self_service', 1);
                $user = User::find($userId);
                $query->where(function ($query) use ($user) {
                    foreach ($user->groups as $group) {
                        $query->orWhereJsonContains(
                            'process_request_tokens.self_service_groups', strval($group->getKey())
                        ); // backwards compatibility
                        $query->orWhereJsonContains(
                            'process_request_tokens.self_service_groups->groups', strval($group->getKey())
                        );
                    }
                    $query->orWhereJsonContains(
                        'process_request_tokens.self_service_groups->users', strval($user->getKey())
                    );
                });
            });
        });

        return $query;
    }
}
