<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Shape;
use Watson\Validating\ValidatingTrait;

/**
 * An event is something that happens during the course of the process execution.
 *
 * @property string $EVN_UID
 * @property string $EVN_NAME
 * @property string $EVN_TYPE
 * @property string $EVN_MARKER
 * @property bool $EVN_IS_INTERRUPTING
 * @property string $EVN_ATTACHED_TO
 * @property bool $EVN_CANCEL_ACTIVITY
 * @property string $EVN_ACTIVITY_REF
 * @property bool $EVN_WAIT_FOR_COMPLETION
 * @property string $EVN_ERROR_NAME
 * @property string $EVN_ERROR_CODE
 * @property string $EVN_ESCALATION_NAME
 * @property string $EVN_ESCALATION_CODE
 * @property string $EVN_CONDITION
 * @property string $EVN_MESSAGE
 * @property string $EVN_OPERATION_NAME
 * @property string $EVN_OPERATION_IMPLEMENTATION_REF
 * @property string $EVN_TIME_DATE
 * @property string $EVN_TIME_CYCLE
 * @property string $EVN_TIME_DURATION
 * @property string $EVN_BEHAVIOR
 */
class Event extends Model implements FlowNodeInterface
{

    use ValidatingTrait, InitializeUidTrait, FlowNodeTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_EVENT';
    protected $primaryKey = 'EVN_UID';
    public $timestamps = false;
    public $incrementing = false;

    const TYPE = 'bpmnEvent';
    const TYPE_START = 'START';
    const TYPE_INTERMEDIATE = 'INTERMEDIATE';
    const TYPE_END = 'END';
    const MARKER_EMPTY = 'EMPTY';
    const MARKER_MESSAGETHROW = 'MESSAGETHROW';
    const MARKER_EMAIL = 'EMAIL';
    const MARKER_MESSAGECATCH = 'MESSAGECATCH';
    const BEHAVIOR_THROW = 'THROW';
    const BEHAVIOR_CATCH = 'CATCH';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'EVN_UID',
        'EVN_NAME',
        'EVN_TYPE',
        'EVN_MARKER',
        'EVN_IS_INTERRUPTING',
        'EVN_ATTACHED_TO',
        'EVN_CANCEL_ACTIVITY',
        'EVN_ACTIVITY_REF',
        'EVN_WAIT_FOR_COMPLETION',
        'EVN_ERROR_NAME',
        'EVN_ERROR_CODE',
        'EVN_ESCALATION_NAME',
        'EVN_ESCALATION_CODE',
        'EVN_CONDITION',
        'EVN_MESSAGE',
        'EVN_OPERATION_NAME',
        'EVN_OPERATION_IMPLEMENTATION_REF',
        'EVN_TIME_DATE',
        'EVN_TIME_CYCLE',
        'EVN_TIME_DURATION',
        'EVN_BEHAVIOR'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'EVN_UID'                          => '',
        'EVN_NAME'                         => null,
        'EVN_TYPE'                         => self::TYPE_END,
        'EVN_MARKER'                       => self::MARKER_EMPTY,
        'EVN_IS_INTERRUPTING'              => true,
        'EVN_ATTACHED_TO'                  => '',
        'EVN_CANCEL_ACTIVITY'              => false,
        'EVN_ACTIVITY_REF'                 => '',
        'EVN_WAIT_FOR_COMPLETION'          => true,
        'EVN_ERROR_NAME'                   => null,
        'EVN_ERROR_CODE'                   => null,
        'EVN_ESCALATION_NAME'              => null,
        'EVN_ESCALATION_CODE'              => null,
        'EVN_CONDITION'                    => null,
        'EVN_MESSAGE'                      => null,
        'EVN_OPERATION_NAME'               => null,
        'EVN_OPERATION_IMPLEMENTATION_REF' => null,
        'EVN_TIME_DATE'                    => null,
        'EVN_TIME_CYCLE'                   => null,
        'EVN_TIME_DURATION'                => null,
        'EVN_BEHAVIOR'                     => self::BEHAVIOR_CATCH
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'EVN_UID'                          => 'string',
        'EVN_NAME'                         => 'string',
        'EVN_TYPE'                         => 'string',
        'EVN_MARKER'                       => 'string',
        'EVN_IS_INTERRUPTING'              => 'bool',
        'EVN_ATTACHED_TO'                  => 'string',
        'EVN_CANCEL_ACTIVITY'              => 'bool',
        'EVN_ACTIVITY_REF'                 => 'string',
        'EVN_WAIT_FOR_COMPLETION'          => 'bool',
        'EVN_ERROR_NAME'                   => 'string',
        'EVN_ERROR_CODE'                   => 'string',
        'EVN_ESCALATION_NAME'              => 'string',
        'EVN_ESCALATION_CODE'              => 'string',
        'EVN_CONDITION'                    => 'string',
        'EVN_MESSAGE'                      => 'string',
        'EVN_OPERATION_NAME'               => 'string',
        'EVN_OPERATION_IMPLEMENTATION_REF' => 'string',
        'EVN_TIME_DATE'                    => 'string',
        'EVN_TIME_CYCLE'                   => 'string',
        'EVN_TIME_DURATION'                => 'string',
        'EVN_BEHAVIOR'                     => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'EVN_UID'                          => 'required|max:32',
        'EVN_NAME'                         => 'nullable|max:255',
        'EVN_TYPE'                         => 'required|in:' . self::TYPE_START . ',' . self::TYPE_INTERMEDIATE . ',' . self::TYPE_END,
        'EVN_MARKER'                       => 'required|in:' . self::MARKER_EMPTY . ',' . self::MARKER_MESSAGETHROW . ',' . self::MARKER_EMAIL . ',' . self::MARKER_MESSAGECATCH,
        'EVN_ATTACHED_TO'                  => 'nullable|max:32',
        'EVN_ACTIVITY_REF'                 => 'nullable|max:32',
        'EVN_ERROR_NAME'                   => 'nullable|max:255',
        'EVN_ERROR_CODE'                   => 'nullable|max:255',
        'EVN_ESCALATION_NAME'              => 'nullable|max:255',
        'EVN_ESCALATION_CODE'              => 'nullable|max:255',
        'EVN_CONDITION'                    => 'nullable|max:255',
        'EVN_OPERATION_NAME'               => 'nullable|max:255',
        'EVN_OPERATION_IMPLEMENTATION_REF' => 'nullable|max:255',
        'EVN_TIME_DATE'                    => 'nullable|max:255',
        'EVN_TIME_CYCLE'                   => 'nullable|max:255',
        'EVN_TIME_DURATION'                => 'nullable|max:255',
        'EVN_BEHAVIOR'                     => 'required|in:' . self::BEHAVIOR_THROW . ',' . self::BEHAVIOR_CATCH,
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'EVN_UID';
    }

    /**
     * Owner process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'PRO_ID', 'PRO_ID');
    }

    /**
     * Shape of the element.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function shape()
    {
        return $this->morphOne(Shape::class, 'shape', 'BOU_ELEMENT_TYPE', 'ELEMENT_UID', 'EVN_UID');
    }
}
