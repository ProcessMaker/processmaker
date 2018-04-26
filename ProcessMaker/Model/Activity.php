<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * An activity is a part of a process that can be a task or a sub process and is executed
 * by the system or a human user.
 *
 * @property string $ACT_UID
 * @property int $PRO_ID
 * @property string $ACT_NAME
 * @property string $ACT_TYPE
 * @property int $ACT_IS_FOR_COMPENSATION
 * @property int $ACT_START_QUANTITY
 * @property int $ACT_COMPLETION_QUANTITY
 * @property string $ACT_TASK_TYPE
 * @property string $ACT_IMPLEMENTATION
 * @property int $ACT_INSTANTIATE
 * @property string $ACT_SCRIPT_TYPE
 * @property string $ACT_SCRIPT
 * @property string $ACT_LOOP_TYPE
 * @property int $ACT_TEST_BEFORE
 * @property int $ACT_LOOP_MAXIMUM
 * @property string $ACT_LOOP_CONDITION
 * @property int $ACT_LOOP_CARDINALITY
 * @property string $ACT_LOOP_BEHAVIOR
 * @property int $ACT_IS_ADHOC
 * @property int $ACT_IS_COLLAPSED
 * @property string $ACT_COMPLETION_CONDITION
 * @property string $ACT_ORDERING
 * @property int $ACT_CANCEL_REMAINING_INSTANCES
 * @property string $ACT_PROTOCOL
 * @property string $ACT_METHOD
 * @property int $ACT_IS_GLOBAL
 * @property string $ACT_REFERER
 * @property string $ACT_DEFAULT_FLOW
 * @property string $ACT_MASTER_DIAGRAM
 */
class Activity extends Model implements FlowNodeInterface
{

    use ValidatingTrait, InitializeUidTrait, FlowNodeTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_ACTIVITY';
    protected $primaryKey = 'ACT_UID';
    public $timestamps = false;
    public $incrementing = false;

    const TYPE = 'bpmnActivity';
    const TYPE_TASK = 'TASK';
    const TASK_TYPE_EMPTY = 'EMPTY';
    const LOOP_TYPE_NONE = 'NONE';
    const LOOP_BEHAVIOR_NONE = 'NONE';
    const ACT_ORDERING_PARALLEL = 'PARALLEL';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'ACT_UID',
        'PRO_ID',
        'ACT_NAME',
        'ACT_TYPE',
        'ACT_IS_FOR_COMPENSATION',
        'ACT_START_QUANTITY',
        'ACT_COMPLETION_QUANTITY',
        'ACT_TASK_TYPE',
        'ACT_IMPLEMENTATION',
        'ACT_INSTANTIATE',
        'ACT_SCRIPT_TYPE',
        'ACT_SCRIPT',
        'ACT_LOOP_TYPE',
        'ACT_TEST_BEFORE',
        'ACT_LOOP_MAXIMUM',
        'ACT_LOOP_CONDITION',
        'ACT_LOOP_CARDINALITY',
        'ACT_LOOP_BEHAVIOR',
        'ACT_IS_ADHOC',
        'ACT_IS_COLLAPSED',
        'ACT_COMPLETION_CONDITION',
        'ACT_ORDERING',
        'ACT_CANCEL_REMAINING_INSTANCES',
        'ACT_PROTOCOL',
        'ACT_METHOD',
        'ACT_IS_GLOBAL',
        'ACT_REFERER',
        'ACT_DEFAULT_FLOW',
        'ACT_MASTER_DIAGRAM'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'ACT_UID'                        => '',
        'PRO_ID'                         => null,
        'ACT_NAME'                       => null,
        'ACT_TYPE'                       => self::TYPE_TASK,
        'ACT_IS_FOR_COMPENSATION'        => false,
        'ACT_START_QUANTITY'             => 1,
        'ACT_COMPLETION_QUANTITY'        => 1,
        'ACT_TASK_TYPE'                  => self::TASK_TYPE_EMPTY,
        'ACT_IMPLEMENTATION'             => null,
        'ACT_INSTANTIATE'                => 0,
        'ACT_SCRIPT_TYPE'                => null,
        'ACT_SCRIPT'                     => null,
        'ACT_LOOP_TYPE'                  => self::LOOP_TYPE_NONE,
        'ACT_TEST_BEFORE'                => false,
        'ACT_LOOP_MAXIMUM'               => 0,
        'ACT_LOOP_CONDITION'             => null,
        'ACT_LOOP_CARDINALITY'           => 0,
        'ACT_LOOP_BEHAVIOR'              => self::LOOP_BEHAVIOR_NONE,
        'ACT_IS_ADHOC'                   => false,
        'ACT_IS_COLLAPSED'               => true,
        'ACT_COMPLETION_CONDITION'       => null,
        'ACT_ORDERING'                   => self::ACT_ORDERING_PARALLEL,
        'ACT_CANCEL_REMAINING_INSTANCES' => true,
        'ACT_PROTOCOL'                   => null,
        'ACT_METHOD'                     => null,
        'ACT_IS_GLOBAL'                  => false,
        'ACT_REFERER'                    => '',
        'ACT_DEFAULT_FLOW'               => '',
        'ACT_MASTER_DIAGRAM'             => ''
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'ACT_UID'                        => 'string',
        'PRO_ID'                         => 'int',
        'ACT_NAME'                       => 'string',
        'ACT_TYPE'                       => 'string',
        'ACT_IS_FOR_COMPENSATION'        => 'bool',
        'ACT_START_QUANTITY'             => 'int',
        'ACT_COMPLETION_QUANTITY'        => 'int',
        'ACT_TASK_TYPE'                  => 'string',
        'ACT_IMPLEMENTATION'             => 'string',
        'ACT_INSTANTIATE'                => 'int',
        'ACT_SCRIPT_TYPE'                => 'string',
        'ACT_SCRIPT'                     => 'string',
        'ACT_LOOP_TYPE'                  => 'string',
        'ACT_TEST_BEFORE'                => 'bool',
        'ACT_LOOP_MAXIMUM'               => 'int',
        'ACT_LOOP_CONDITION'             => 'string',
        'ACT_LOOP_CARDINALITY'           => 'int',
        'ACT_LOOP_BEHAVIOR'              => 'string',
        'ACT_IS_ADHOC'                   => 'bool',
        'ACT_IS_COLLAPSED'               => 'bool',
        'ACT_COMPLETION_CONDITION'       => 'string',
        'ACT_ORDERING'                   => 'string',
        'ACT_CANCEL_REMAINING_INSTANCES' => 'bool',
        'ACT_PROTOCOL'                   => 'string',
        'ACT_METHOD'                     => 'string',
        'ACT_IS_GLOBAL'                  => 'bool',
        'ACT_REFERER'                    => 'string',
        'ACT_DEFAULT_FLOW'               => 'string',
        'ACT_MASTER_DIAGRAM'             => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'ACT_UID'                  => 'required|max:32',
        'ACT_NAME'                 => 'required|max:255',
        'ACT_TYPE'                 => 'required|max:30',
        'ACT_START_QUANTITY'       => 'nullable|max:11',
        'ACT_COMPLETION_QUANTITY'  => 'nullable|max:11',
        'ACT_TASK_TYPE'            => 'required|max:20',
        'ACT_SCRIPT_TYPE'          => 'nullable|max:255',
        'ACT_LOOP_TYPE'            => 'required|max:20',
        'ACT_LOOP_MAXIMUM'         => 'nullable|max:11',
        'ACT_LOOP_CONDITION'       => 'nullable|max:100',
        'ACT_LOOP_CARDINALITY'     => 'nullable|max:11',
        'ACT_LOOP_BEHAVIOR'        => 'nullable|max:20',
        'ACT_COMPLETION_CONDITION' => 'nullable|max:255',
        'ACT_ORDERING'             => 'nullable|max:20',
        'ACT_PROTOCOL'             => 'nullable|max:255',
        'ACT_METHOD'               => 'nullable|max:255',
        'ACT_REFERER'              => 'nullable|max:32',
        'ACT_DEFAULT_FLOW'         => 'nullable|max:32',
        'ACT_MASTER_DIAGRAM'       => 'nullable|max:32',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'ACT_UID';
    }
}
