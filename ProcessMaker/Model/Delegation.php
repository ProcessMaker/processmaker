<?php

namespace ProcessMaker\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ProcessMaker\Model\Traits\Uuid;
use ProcessMaker\Nayra\Bpmn\TokenTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Ramsey\Uuid\Uuid as UuidGenerator;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Delegation
 *
 * @package ProcessMaker\Model
 * 
 * @param integer id
 * @param string uid
 * @param integer application_id
 * @param integer index
 * @param integer previous
 * @param integer last_index
 * @param integer task_id
 * @param string type
 * @param integer thread
 * @param string thread_status
 * @param string priority
 * @param Carbon delegate_date
 * @param Carbon init_date
 * @param Carbon finish_date
 * @param Carbon task_due_date
 * @param Carbon risk_date
 * @param float duration
 * @param float queue_duration
 * @param float delay_duration
 * @param boolean started
 * @param boolean finished
 * @param boolean delayed
 * @param float app_overdue_percentage
 * @param integer user_id
 * 
 */
class Delegation extends Model implements TokenInterface
{
    use ValidatingTrait,
        Uuid,
        TokenTrait;

    // We do not store timestamps for these tables
    public $timestamps = false;

    /**
     * Thread status types
     */
    const THREAD_STATUS_OPEN = 'ACTIVE';
    const THREAD_STATUS_CLOSED = 'CLOSED';

    const TYPE_NORMAL = 'NORMAL';

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'delegate_date',
        'init_date',
        'finish_date',
        'task_due_date',
        'risk_date'
    ];

    protected $fillable = [
        'id',
        'uid',
        'application_id',
        'index',
        'previous',
        'last_index',
        'task_id',
        'type',
        'thread',
        'thread_status',
        'priority',
        'delegate_date',
        'init_date',
        'finish_date',
        'task_due_date',
        'risk_date',
        'duration',
        'queue_duration',
        'delay_duration',
        'started',
        'finished',
        'delayed',
        'app_overdue_percentage',
        'user_id'
    ];
    protected $appends = [
        'definition'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'application_id' => 'exists:APPLICATION,id',
        //'task_id' => 'exists:tasks,id',
        'user_id' => 'exists:users,id',
        'delegate_date' => 'required',
        'started' => 'required|boolean',
        'finished' => 'required|boolean',
        'delayed' => 'required|boolean',
    ];

    /**
     * Boot delegation as a token instance.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments=[])
    {
        parent::__construct($arguments);
        $this->bootElement([
            $this->application()
        ]);
        $this->setId(UuidGenerator::uuid4());
    }

    /**
     * Returns the relationship of the parent application
     *
     * @return BelongsTo
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Returns the relationship of the parent user
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns the relationship of the parent task
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /*
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }
    
    public function getDefinitionAttribute()
    {
        $definitions = $this->application->process->getDefinitions();
        return $definitions->getActivity($this->element_ref)->getProperties();
    }
}
