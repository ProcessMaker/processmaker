<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Laravel\Scout\Searchable;
use Log;
use ProcessMaker\Events\ProcessUpdated;
use ProcessMaker\Exception\PmqlMethodException;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\IntermediateCatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\SignalEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Traits\SqlsrvSupportTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Throwable;
use ProcessMaker\Repositories\BpmnDocument;

/**
 * Represents an Eloquent model of a Request which is an instance of a Process.
 *
 * @property string $id
 * @property string $process_id
 * @property string $user_id
 * @property string $process_collaboration_id
 * @property string $participant_id
 * @property string $name
 * @property string $status
 * @property string $data
 * @property \Carbon\Carbon $initiated_at
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property Process $process
 * @property ProcessRequestLock[] $locks
 * @property ProcessRequestToken $ownerTask
 * @method static ProcessRequest find($id)
 *
 * @OA\Schema(
 *   schema="processRequestEditable",
 *   @OA\Property(property="user_id", type="string", format="id"),
 *   @OA\Property(property="callable_id", type="string", format="id"),
 *   @OA\Property(property="data", type="object"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "COMPLETED", "ERROR", "CANCELED"}),
 *   @OA\Property(property="name", type="string"),
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
class ProcessRequest extends Model implements ExecutionInstanceInterface, HasMedia
{
    use ExecutionInstanceTrait;
    use SerializeToIso8601;
    use HasMediaTrait;
    use ExtendedPMQL;
    use SqlsrvSupportTrait;
    use HideSystemResources;
    use Searchable;

    protected $connection = 'data';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
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
        'data'
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

    /**
     * Determine whether the item should be indexed.
     *
     * @return boolean
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
                break;
            case 'participants':
                return $this->participants()->get()->pluck('id');
                break;
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
     * @return boolean True if the user participated in this Case in some way
     */
    public function hasUserParticipated(User $user)
    {
        return $this->tokens()
                ->where('user_id', $user->id)
                ->exists();
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

        return $screen;
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
     *
     */
    public function tokens()
    {
        return $this->hasMany(ProcessRequestToken::class);
    }

    /**
     * Get the creator/author of this request.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get collaboration of this request.
     *
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
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    /**
     * Get users of the request.
     *
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
                $matches = ProcessRequest::search($filter)->take(10000)->get()->pluck('id');
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
                $result[] = [
                    'key' => $key,
                    'value' => $value
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
        \Log::error($exception);
        if (!$this->isNonPersistent()) {
            $this->save();
        }
    }

    public function childRequests()
    {
        return $this->hasMany(ProcessRequest::class, 'parent_request_id');
    }

    public function parentRequest()
    {
        return $this->belongsTo(ProcessRequest::class, 'parent_request_id');
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
     * PMQL value alias for request field
     *
     * @param string $value
     *
     * @return callback
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
     * @return callback
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
     * @return callback
     */
    private function valueAliasRequester($value, $expression)
    {
        $user = User::where('username', $value)->get()->first();

        if ($user) {
            $requests = ProcessRequest::where('user_id', $expression->operator, $user->id)->get();
            return function ($query) use ($requests) {
                $query->whereIn('id', $requests->pluck('id'));
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
     * @return callback
     */
    private function valueAliasParticipant($value, $expression)
    {
        $user = User::where('username', $value)->get()->first();

        if ($user) {
            $tokens = ProcessRequestToken::where('user_id', $expression->operator, $user->id)->get();

            return function ($query) use ($tokens) {
                $query->whereIn('id', $tokens->pluck('process_request_id'));
            };
        } else {
            throw new PmqlMethodException('participant', 'The specified participant username does not exist.');
        }
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
     * Get the process version used by this request
     *
     * @return ProcessVersion
     */
    public function userPermissions()
    {
        return $this->hasMany(RequestUserPermission::class, 'request_id');
    }

    /**
     * Filter process started with user
     *
     * @param $query
     *
     * @param $id User id
     */
    public function scopeRequestsThatUserCan($query, $permission, User $user)
    {
        if ($permission === 'can_view' && $user->can('view-all_requests')) {
            return $query;
        }
        $query->whereHas('userPermissions', function ($query) use ($permission, $user) {
            $query->where('user_id', $user->getKey());
            $query->where($permission, true);
        });
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
        foreach ($this->tokens as $token) {
            $element = $token->getDefinition(true, $this->tokens->toArray());
            if ($element instanceof IntermediateCatchEventInterface) {
                foreach ($element->getEventDefinitions() as $eventDefinition) {
                    if ($eventDefinition instanceof SignalEventDefinitionInterface) {
                        $signal = $eventDefinition->getProperty('signal');
                        if ($signal) {
                            $signalEvents[]= $signal->getId();
                        }
                    }
                }
            }
        }
        $this->signal_events = $signalEvents;
        $this->save();
    }

    /**
     * Update and merge with the latest stored data
     *
     * @return array
     */
    public function mergeLatestStoredData()
    {
        $store = $this->getDataStore();
        $latest = ProcessRequest::find($this->getId());
        $this->data = $store->updateArray($latest->data);
        return $this->data;
    }

    /**
     * Locks required to the request
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function locks()
    {
        return $this->hasMany(ProcessRequestLock::class);
    }

    /**
     * Request a lock
     *
     * @param int $tokenId
     *
     * @return ProcessRequestLock
     */
    public function lock($tokenId)
    {
        return $this->locks()->create(['process_request_token_id' => $tokenId]);
    }

    public function unlock()
    {
        $first = $this->locks()->orderBy('id')->first();
        if ($first) {
            $first->delete();
        }
    }

    public function hasLock(ProcessRequestLock $lock)
    {
        $first = $this->locks()->orderBy('id')->first();
        return !$first || $first->getKey() === $lock->getKey();
    }

    /**
     * Returns true if the request persists
     *
     * @return boolean
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
     * @param boolean $forceParse
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
    public function notifyProcessUpdated($eventName)
    {
        $event = new ProcessUpdated($this, $eventName);
        event($event);
        if ($this->parentRequest) {
            $this->parentRequest->notifyProcessUpdated($eventName);
            event($event);
        }
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
}
