<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Laravel\Scout\Searchable;
use Log;
use ProcessMaker\Events\ProcessUpdated;
use ProcessMaker\Exception\PmqlMethodException;
use ProcessMaker\Helpers\DataTypeHelper;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\IntermediateCatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\SignalEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;
use ProcessMaker\Query\Expression;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\ForUserScope;
use ProcessMaker\Traits\HasUuids;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Traits\SqlsrvSupportTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Throwable;

/**
 * Represents an Eloquent model of a Request which is an instance of a Process.
 *
 * @property string $id
 * @property string $process_id
 * @property string $user_id
 * @property string $process_collaboration_id
 * @property string $participant_id
 * @property string $name
 * @property string $case_title
 * @property int $case_title_formatted
 * @property string $user_viewed_at
 * @property int $case_number
 * @property string $status
 * @property array $data
 * @property string $collaboration_uuid
 * @property Carbon $initiated_at
 * @property Carbon $completed_at
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property Process $process
 * @property ProcessRequestLock[] $locks
 * @property ProcessRequestToken $ownerTask
 * @property ProcessVersion $processVersion
 * @method static ProcessRequest find($id)
 * @method static ProcessRequest findOrFail($id)
 *
 * @OA\Schema(
 *   schema="processRequestEditable",
 *   @OA\Property(property="user_id", type="string", format="id"),
 *   @OA\Property(property="callable_id", type="string", format="id"),
 *   @OA\Property(property="data", type="object"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "COMPLETED", "ERROR", "CANCELED"}),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="case_title", type="string"),
 *   @OA\Property(property="case_title_formatted", type="string"),
 *   @OA\Property(property="user_viewed_at", type="string"),
 *   @OA\Property(property="case_number", type="integer"),
 *   @OA\Property(property="process_id", type="integer"),
 *   @OA\Property(property="process", type="object"),
 * ),
 * @OA\Schema(
 *   schema="processRequest",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/processRequestEditable"),
 *       @OA\Schema(
 *           type="object",
 *           @OA\Property(property="id", type="string", format="id"),
 *           @OA\Property(property="process_id", type="string", format="id"),
 *           @OA\Property(property="process_collaboration_id", type="string", format="id"),
 *           @OA\Property(property="participant_id", type="string", format="id"),
 *           @OA\Property(property="process_category_id", type="string", format="id"),
 *           @OA\Property(property="created_at", type="string", format="date-time"),
 *           @OA\Property(property="updated_at", type="string", format="date-time"),
 *           @OA\Property(property="user", @OA\Schema(ref="#/components/schemas/users")),
 *           @OA\Property(property="participants", type="array", @OA\Items(ref="#/components/schemas/users")),
 *      )
 *   },
 * )
 */
class ProcessRequest extends ProcessMakerModel implements ExecutionInstanceInterface, HasMedia
{
    use ExecutionInstanceTrait;
    use ExtendedPMQL;
    use ForUserScope;
    use HasUuids;
    use HideSystemResources;
    use InteractsWithMedia;
    use Searchable;
    use SerializeToIso8601;
    use SqlsrvSupportTrait;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
        'data',
    ];

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $ids = [
        'process_id',
        'process_collaboration_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'completed_at' => 'datetime:c',
        'initiated_at' => 'datetime:c',
        'data' => 'array',
        'errors' => 'array',
        'do_not_sanitize' => 'array',
        'signal_events' => 'array',
        'locked_at' => 'datetime:c',
    ];

    /**
     * Associated records that can be included with this model
     *
     * @var array
     */
    public static $allowedIncludes = [
        'assigned',
        'process',
        'participants',
    ];

    const DEFAULT_CASE_TITLE = 'Case #{{_request.case_number}}';

    /**
     * Determine whether the item should be indexed.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        $setting = Setting::byKey('indexed-search');
        if ($setting && $setting->config['enabled'] === true) {
            return true;
        } else {
            return false;
        }
    }

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
            'name' => $this->name,
            'data' => json_encode($dataToInclude),
        ];
    }

    /**
     * Boot the model as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument = [])
    {
        parent::__construct($argument);
        $this->bootElement([]);
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
        $self = new self();
        $unique = Rule::unique($self->getConnectionName() . '.process_requests')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique, 'alpha_spaces'],
            'data' => 'required',
            'status' => 'in:ACTIVE,COMPLETED,ERROR,CANCELED',
            'process_id' => 'required|exists:processes,id',
            'process_collaboration_id' => 'nullable|exists:process_collaborations,id',
            'user_id' => 'exists:users,id',
        ];
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
                                   ->whereNull('element_id')
                                   ->get()->pluck('notifiable_type');

        foreach ($notifiableTypes as $notifiableType) {
            $userIds = $userIds->merge($this->getNotifiableUserIds($notifiableType));
        }

        $userIds = $userIds->unique();

        $notifiables = $notifiableTypes->implode(', ');
        $users = $userIds->implode(', ');
        Log::debug("Sending request $notificationType notification to $notifiables (users: $users)");

        return User::whereIn('id', $userIds)->get();
    }

    public function getNotifiableUserIds($notifiableType)
    {
        switch ($notifiableType) {
            case 'requester':
                return collect([$this->user_id]);
            case 'participants':
                return $this->participants()->get()->pluck('id');
            case 'manager':
                return collect([$this->process()->first()->manager_id]);
            default:
                return collect([]);
        }
    }

    /**
     * Determines if a user has participated in this application.  This is done by checking if any delegations
     * match this application and passed in user.
     *
     * @param User $user User to check
     *
     * @return bool True if the user participated in this Case in some way
     */
    public function hasUserParticipated(User $user)
    {
        return $this->tokens()
                ->where('user_id', $user->id)
                ->exists();
    }

    /**
     * Check is user can claim an active self service task.
     *
     * @link https://processmaker.atlassian.net/browse/FOUR-4126
     * @param User $user
     * @return bool
     */
    public function canUserClaimASelfServiceTask(User $user)
    {
        // Get active self service tasks
        $tasks = $this->tokens()
            ->where('status', 'ACTIVE')
            ->where('is_self_service', 1)
            ->get();
        // Check if user can claim any of the active self service tasks
        foreach ($tasks as $task) {
            if ($user->canSelfServe($task)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the id of the summary screen that is associated with the end event in which the request
     * finished
     *
     * @return null
     */
    public function getSummaryScreen()
    {
        $endEvents = $this->tokens()->where('element_type', 'end_event')->get();

        if ($endEvents->count(0) === 0) {
            return null;
        }

        //get the first token that is and end event to get the summary screen
        $definition = $endEvents->first()->getDefinition();
        $screen = empty($definition['screenRef']) ? null : Screen::find($definition['screenRef']);

        if ($screen) {
            return $screen->versionFor($this);
        }

        return null;
    }

    /**
     * Get screen requested
     *
     * @return array of screens
     */
    public function getScreensRequested()
    {
        $tokens = $this->tokens()
            ->whereNotIn('element_type', ['startEvent', 'end_event'])
            ->where('status', 'CLOSED')
            ->orderBy('completed_at')
            ->get();
        $screens = [];
        foreach ($tokens as $token) {
            $definition = $token->getDefinition();
            if (array_key_exists('screenRef', $definition)) {
                $screen = $token->getScreenVersion();
                if ($screen) {
                    $screen->element_name = $token->element_name;
                    $screen->element_type = $token->element_type;
                    $dataManager = new DataManager();
                    $screen->data = $dataManager->getData($token, true);
                    $screen->screen_id = $screen->id;
                    $screen->id = $token->id;
                    $screens[] = $screen;
                }
            }
        }

        return $screens;
    }

    /**
     * Get tokens of the request.
     */
    public function tokens()
    {
        return $this->hasMany(ProcessRequestToken::class);
    }

    /**
     * Get the creator/author of this request.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get collaboration of this request.
     */
    public function collaboration()
    {
        return $this->belongsTo(
            ProcessCollaboration::class,
            'process_collaboration_id'
        );
    }

    /**
     * Get the creator/author of this request.
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    /**
     * Get users of the request.
     */
    public function assigned()
    {
        return $this->hasMany(ProcessRequestToken::class)
                ->with('user')
                ->whereNotIn('element_type', ['scriptTask']);
    }

    /**
     * Filter processes with a string
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
                $query->where(DB::raw('LOWER(name)'), 'like', $filter)
                    ->orWhere(DB::raw('LOWER(data)'), 'like', $filter)
                    ->orWhere(DB::raw('id'), 'like', $filter)
                    ->orWhere(DB::raw('LOWER(status)'), 'like', $filter)
                    ->orWhere('initiated_at', 'like', $filter)
                    ->orWhere('created_at', 'like', $filter)
                    ->orWhere('updated_at', 'like', $filter);
            });
        }

        return $query;
    }

    /**
     * Filter process started with user
     *
     * @param $query
     *
     * @param $id User id
     */
    public function scopeStartedMe($query, $id)
    {
        $query->where('user_id', '=', $id);
    }

    /**
     * Filter process not completed
     *
     * @param $query
     */
    public function scopeInProgress($query)
    {
        $query->where('status', '=', 'ACTIVE');
    }

    /**
     * Filter process completed
     *
     * @param $query
     */
    public function scopeCompleted($query)
    {
        $query->where(function ($query) {
            $query->where('status', '=', 'COMPLETED');
        });
    }

    /**
     * Filter process not completed
     *
     * @param $query
     */
    public function scopeNotCompleted($query)
    {
        $query->where('status', '!=', 'COMPLETED');
        $query->where('status', '!=', 'CANCELED');
    }

    /**
     * Returns the list of users that have participated in the request
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function participants()
    {
        return $this->hasManyThrough(
            User::class,
            ProcessRequestToken::class,
            'process_request_id',
            'id',
            $this->getKeyName(),
            'user_id'
        )
                ->distinct();
    }

    /**
     * Returns the summary data in an array key/value
     */
    public function summary()
    {
        $result = [];
        if (is_array($this->data)) {
            foreach ($this->getRequestData() as $key => $value) {
                $type = DataTypeHelper::determineType('', $value);
                $result[] = [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type
                ];
            }
        }
        return $result;
    }

    /**
     * Records an error occurred during the execution of the process.
     *
     * @param Throwable $exception
     * @param FlowElementInterface $element
     */
    public function logError(Throwable $exception, FlowElementInterface $element = null)
    {
        // Get the first line of the message
        $array = explode("\n", $exception->getMessage());
        $message = '';
        $body = '';
        while ($array) {
            $message = array_shift($array);
            if (trim($message)) {
                $body = implode("\n", $array);
                break;
            }
        }
        $error = [
            'message' => $message,
            'body' => $body,
            'element_id' => $element ? $element->getId() : null,
            'element_name' => $element ? $element->getName() : null,
            'created_at' => Carbon::now('UTC')->format('c'),
        ];
        $errors = $this->errors ?: [];
        $errors[] = $error;
        $this->errors = $errors;
        $this->status = 'ERROR';
        Log::error($exception);
        if (!$this->isNonPersistent()) {
            $this->save();
        }
    }

    public function childRequests()
    {
        return $this->hasMany(self::class, 'parent_request_id');
    }

    public function parentRequest()
    {
        return $this->belongsTo(self::class, 'parent_request_id');
    }

    /**
     * Scheduled task of the request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scheduledTasks()
    {
        return $this->hasMany(ScheduledTask::class, 'process_request_id');
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
     * PMQL field alias (started = initiated_at)
     *
     * @return string
     */
    public function fieldAliasStarted()
    {
        return 'initiated_at';
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
     * PMQL value alias for request field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasRequest($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $processes = Process::where('name', $expression->operator, $value)->get();
            $query->whereIn('process_id', $processes->pluck('id'));
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
        $statusMap = [
            'in progress' => 'ACTIVE',
            'completed' => 'COMPLETED',
            'error' => 'ERROR',
            'canceled' => 'CANCELED',
        ];

        $value = mb_strtolower($value);

        return function ($query) use ($value, $statusMap, $expression) {
            if (array_key_exists($value, $statusMap)) {
                $value = $statusMap[$value];
            }
            $query->where('status', $expression->operator, $value);
        };
    }

    /**
     * PMQL value alias for requester field
     *
     * @param string $value
     *
     * @return callable
     */
    private function valueAliasRequester($value, $expression)
    {
        $user = User::where('username', $value)->get()->first();

        if ($user) {
            return function ($query) use ($user, $expression) {
                $query->where('user_id', $expression->operator, $user->id);
            };
        } else {
            throw new PmqlMethodException('requester', 'The specified requester username does not exist.');
        }
    }

    /**
     * PMQL value alias for participant field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasParticipant($value, $expression)
    {
        $user = User::where('username', $value)->get()->first();

        if ($user) {
            return function ($query) use ($user, $expression) {
                $query->whereIn('id', function ($subquery) use ($user, $expression) {
                    $subquery->select('process_request_id')->from('process_request_tokens')
                        ->where('user_id', $expression->operator, $user->id)
                        ->whereIn('element_type', ['task', 'userTask', 'startEvent']);
                });
            };
        } else {
            throw new PmqlMethodException('participant', 'The specified participant username does not exist.');
        }
    }

    /**
     * PMQL value alias for participant field by fullname.
     * @param string $value
     * @return callable
     */
    public function valueAliasParticipantByFullName($value, $expression)
    {
        return function ($query) use ($value, $expression) {
            $query->whereIn('id', function ($subquery) use ($value, $expression) {
                $subquery->select('process_request_id')->from('process_request_tokens')
                    ->whereIn('user_id', function ($subquery) use ($value, $expression) {
                        $subquery->select('id')
                            ->from('users')
                            ->whereRaw("CONCAT(firstname, ' ', lastname) " . $expression->operator . ' ?', [$value]);
                    })
                    ->whereIn('element_type', ['task', 'userTask', 'startEvent']);
            });
        };
    }

    /**
     * PMQL value alias for the alternative field in the process version
     *
     * @param string value
     * @param ProcessMaker\Query\Expression expression
     *
     * @return callable
     */
    public function valueAliasAlternative(string $value, Expression $expression): callable
    {
        return function ($query) use ($expression, $value) {
            $query->whereHas('processVersion', function ($query) use ($expression, $value) {
                $query->where('alternative', $expression->operator, $value);
            });
        };
    }

    /**
     * Get the process version used by this request
     *
     * @return ProcessVersion
     */
    public function processVersion()
    {
        return $this->belongsTo(ProcessVersion::class, 'process_version_id');
    }

    /**
     * Update the current catch events for the requests
     *
     * @param TokenInterface $token
     *
     * @return void
     */
    public function updateCatchEvents()
    {
        $signalEvents = [];
        foreach ($this->getTokens() as $token) {
            $element = $token->getOwnerElement();
            if ($element instanceof IntermediateCatchEventInterface) {
                foreach ($element->getEventDefinitions() as $eventDefinition) {
                    if ($eventDefinition instanceof SignalEventDefinitionInterface) {
                        $signal = $eventDefinition->getProperty('signal');
                        if ($signal) {
                            $signalEvents[] = $signal->getId();
                        }
                    }
                }
            }
            // Activity with a boundary signal event
            if ($element instanceof ActivityInterface) {
                $boundaryElements = $element->getBoundaryEvents();
                foreach ($boundaryElements as $boundary) {
                    foreach ($boundary->getEventDefinitions() as $eventDefinition) {
                        if ($eventDefinition instanceof SignalEventDefinitionInterface) {
                            $signal = $eventDefinition->getProperty('signal');
                            if ($signal) {
                                $signalEvents[] = $signal->getId();
                            }
                        }
                    }
                }
            }
        }
        $this->signal_events = $signalEvents;
    }

    /**
     * Update and merge with the latest stored data
     *
     * @return array
     */
    public function mergeLatestStoredData()
    {
        $store = $this->getDataStore();
        $latest = self::select('data')->find($this->getId());
        $this->data = $store->updateArray($latest->data);

        return $this->data;
    }

    /**
     * Returns true if the request persists
     *
     * @return bool
     */
    public function isNonPersistent()
    {
        return $this->getProcess()->isNonPersistent();
    }

    /**
     * Get managed data from the process request
     *
     * @return array
     */
    public function getRequestData()
    {
        $dataManager = new DataManager();

        return $dataManager->getRequestData($this);
    }

    /**
     * @return self
     */
    public function loadProcessRequestInstance()
    {
        $process = $this->processVersion ?? $this->processVersion()->first() ?? $this->process ?? $this->process()->first();
        $storage = $process->getDefinitions();
        $callableId = $this->callable_id;
        $process = $storage->getProcess($callableId);
        $dataStore = $storage->getFactory()->createDataStore();
        $dataStore->setData($this->data);
        $this->setId($this->getKey());
        $this->setProcess($process);
        $this->setDataStore($dataStore);

        return $this;
    }

    /**
     * Get the BPMN definitions version of the process that is running.
     *
     * @param bool $forceParse
     * @param mixed $engine
     *
     * @return BpmnDocument
     */
    public function getVersionDefinitions($forceParse = false, $engine = null)
    {
        $processVersion = $this->processVersion ?: $this->process;

        return $processVersion->getDefinitions($forceParse, $engine);
    }

    /**
     * Notify a process update
     *
     * @param string $eventName
     */
    public function notifyProcessUpdated($eventName, TokenInterface $token = null)
    {
        $event = new ProcessUpdated($this, $eventName, $token);
        if ($this->parentRequest) {
            $this->parentRequest->notifyProcessUpdated($eventName, $token);
        }
        event($event);
    }

    /**
     * Owner task of the sub process
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ownerTask()
    {
        return $this->hasOne(ProcessRequestToken::class, 'subprocess_request_id', 'id');
    }

    /**
     * Media files formatted for screen builder file controls
     *
     * @return object
     */
    public function requestFiles(bool $includeToken = false)
    {
        $media = Media::getFilesRequest($this);

        return (object) $media->mapToGroups(function ($file) use ($includeToken) {
            $dataName = $file->getCustomProperty('data_name');
            $info = [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
            ];
            if ($includeToken) {
                $info['token'] = md5($dataName . $file->id . $file->created_at);
            }

            return [$dataName => $info];
        })->toArray();
    }

    public function downloadFile($fileId)
    {
        // Get all files for process and all subprocesses ..
        $filtered = Media::getFilesRequest($this, $fileId);

        if (!$filtered) {
            return null;
        }

        $path = Storage::disk('public')->path($filtered['id'] . '/' . $filtered['file_name']);

        return $path;
    }

    public function getMedia(string $collectionName = 'default', $filters = []): Collection
    {
        return Media::getFilesRequest($this);
    }

    public function getErrors()
    {
        if ($this->errors) {
            return $this->errors;
        }

        // select tokens with errors
        return $this->tokens()
            ->select('token_properties->error as message', 'created_at', 'element_name')
            ->where('status', '=', ActivityInterface::TOKEN_STATE_FAILING)
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get the case title from the process
     *
     * @return string
     */
    public function getCaseTitleFromProcess(): string
    {
        // check if $this->process relation is loaded
        if ($this->process && $this->process instanceof Process) {
            $caseTitle = $this->process->case_title;
        } else {
            $caseTitle = $this->process()->select('case_title')->first()->case_title;
        }

        return $caseTitle ?: self::DEFAULT_CASE_TITLE;
    }

    /**
     * Evaluate the case title mustache expression
     *
     * @param string $mustacheTitle
     * @param array $data
     * @param bool $formatted
     * @return string
     */
    public function evaluateCaseTitle(string $mustacheTitle, array $data, bool $formatted = false): string
    {
        if ($formatted) {
            $mustache = new MustacheExpressionEvaluator([
                'escape' => function ($value) {
                    return '<b>' .
                        htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                        '</b>';
                },
            ]);
            $title = $mustache->render($mustacheTitle, $data);
            // multi-byte 200 characters limit
            $titleParts = preg_split('/(<b>|<\/b>)/', $title, -1, PREG_SPLIT_DELIM_CAPTURE);
            $title = '';
            $size = 200;
            $len = 0;
            foreach ($titleParts as $i => $part) {
                if ($part === '<b>' || $part === '</b>') {
                    $title .= $part;
                    continue;
                }
                if ($len + mb_strlen($part) >= $size) {
                    $part = mb_substr($part, 0, $size - $len);
                    $title .= $part;
                    $nextPart = $titleParts[$i + 1] ?? '';
                    if ($nextPart === '</b>') {
                        $title .= $nextPart;
                    }
                    break;
                }
                $title .= $part;
                $len += mb_strlen($part);
            }
        } else {
            $mustache = new MustacheExpressionEvaluator();
            $title = $mustache->render($mustacheTitle, $data);
            // multi-byte 200 characters limit
            $title = mb_substr($title, 0, 200);
        }

        return $title;
    }

    public function isSystem()
    {
        $systemCategories = ProcessCategory::where('is_system', true)->pluck('id');

        return DB::table('category_assignments')
            ->where('assignable_type', Process::class)
            ->where('assignable_id', $this->process_id)
            ->where('category_type', ProcessCategory::class)
            ->whereIn('category_id', $systemCategories)
            ->exists();
    }

    public function getProcessVersionAlternativeAttribute(): string | null
    {
        if (class_exists('ProcessMaker\Package\PackageABTesting\Models\Alternative')) {
            return $this->processVersion?->alternative;
        }

        return null;
    }
}
