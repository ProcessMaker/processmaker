<?php

namespace ProcessMaker\Models;

use Log;
use Carbon\Carbon;
use ProcessMaker\Models\User;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Nayra\Bpmn\TokenTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Traits\SerializeToIso8601;
use \Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Query\Traits\PMQL;

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
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $due_at
 * @property \Carbon\Carbon $initiated_at
 * @property \Carbon\Carbon $riskchanges_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property ProcessRequest $request
 *
 * @OA\Schema(
 *   schema="processRequestTokenEditable",
 *   @OA\Property(property="user_id", type="string", format="id"),
 *   @OA\Property(property="status", type="string"),
 *   @OA\Property(property="due_at", type="date-time"),
 *   @OA\Property(property="initiated_at", type="string", format="date-time"),
 *   @OA\Property(property="riskchanges_at", type="string", format="date-time"),
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
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          @OA\Property(property="initiated_at", type="string", format="date-time"),
 *       )
 *   }
 * )
 */
class ProcessRequestToken extends Model implements TokenInterface
{
    use PMQL;
    use TokenTrait;
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $guarded
     */
    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
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
        'advanceStatus'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'completed_at',
        'due_at',
        'initiated_at',
        'riskchanges_at',
    ];

    /**
     * Boot application as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument=[])
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
        Log::debug("Sending task $notificationType notification to $notifiables (users: $users)");

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
            default:
                return collect([]);
        }
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    /**
     * Get the request of the token.
     *
     */
    public function processRequest()
    {
        return $this->belongsTo(ProcessRequest::class, 'process_request_id');
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
     * Get the BPMN definition of the element where the token is.
     *
     * @return array
     */
    public function getDefinition()
    {
        $definitions = $this->processRequest->process->getDefinitions();
        $element = $definitions->findElementById($this->element_id);
        if (!$element) {
            return [];
        }
        return $element->getBpmnElementInstance()->getProperties();
    }

    /**
     * Get the BPMN element node where the token is currently located.
     *
     * @return \ProcessMaker\Nayra\Storage\BpmnElement
     */
    public function getBpmnDefinition()
    {
        $definitions = $this->processRequest->process->getDefinitions();
        return $definitions->findElementById($this->element_id);
    }

    /**
     * Get the form assigned to the task.
     *
     * @return Screen
     */
    public function getScreen()
    {
        $definition = $this->getDefinition();
        return empty($definition['screenRef']) ? null : Screen::find($definition['screenRef']);
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

        return $result;
    }

    /**
     * Check if the user has access to reassign this task
     *
     * @param \ProcessMaker\Models\User $user
     */
    public function authorizeReassignment(User $user)
    {
        if ($user->can('update', $this)) {
            $definitions = $this->getDefinition();
            if (empty($definitions['allowReassignment'])) {
                throw new AuthorizationException("Not authorized to reassign this task");
            }
            return true;
        } else {
            throw new AuthorizationException("Not authorized to view this task");
        }
    }
}
