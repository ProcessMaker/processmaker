<?php

namespace ProcessMaker\Models;

use Log;
use Carbon\Carbon;
use ProcessMaker\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
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
 * @property string $status
 * @property string $data
 * @property \Carbon\Carbon $initiated_at
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property Process $process
 *
 * @OA\Schema(
 *   schema="requestsEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="process_id", type="string", format="id"),
 *   @OA\Property(property="callable_id", type="string", format="id"),
 *   @OA\Property(property="data", type="string", format="json"),
 *   @OA\Property(property="status", type="string", enum={"DRAFT", "ACTIVE", "COMPLETED"}),
 * ),
 * @OA\Schema(
 *   schema="requests",
 *   allOf={@OA\Schema(ref="#/components/schemas/requestsEditable")},
 *   @OA\Property(property="id", type="string", format="id"),
 *
 *   @OA\Property(property="process_collaboration_id", type="string", format="id"),
 *   @OA\Property(property="user_id", type="string", format="id"),
 *   @OA\Property(property="participant_id", type="string", format="id"),
 *
 *   @OA\Property(property="process_category_id", type="string", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ProcessRequest extends Model implements ExecutionInstanceInterface, HasMedia
{

    use ExecutionInstanceTrait;
    use SerializeToIso8601;
    use HasMediaTrait;

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
     * Boot the model as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument = [])
    {
        parent::__construct($argument);
        $this->bootElement([]);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $manager = new TaskSchedulerManager();
            $manager->registerIntermediateTimerEvents($model);
        });

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
        $unique = Rule::unique('process_requests')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique],
            'data' => 'required',
            'status' => 'in:ACTIVE,COMPLETED,ERROR',
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
        return $this->belongsTo(ProcessCollaboration::class,
                'process_collaboration_id');
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
        $query->where('status', '=', 'COMPLETED');
    }

    /**
     * Returns the list of users that have participated in the request
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function participants()
    {
        return $this->hasManyThrough(User::class, ProcessRequestToken::class,
                    'process_request_id', 'id', $this->getKeyName(), 'user_id')
                ->distinct();
    }

    /**
     * Returns the summary data in an array key/value
     */
    public function summary()
    {
        $result = [];
        if (is_array($this->data)) {
            foreach ($this->data as $key => $value) {
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
        $this->save();
    }
}
