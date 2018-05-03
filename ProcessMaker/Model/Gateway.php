<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Shape;
use Watson\Validating\ValidatingTrait;

/**
 * A gateway is used to control the flow inside the process.
 *
 * @property string $GAT_UID
 * @property string $GAT_NAME
 * @property string $GAT_TYPE
 * @property string $GAT_DIRECTION
 * @property bool $GAT_INSTANTIATE
 * @property string $GAT_EVENT_GATEWAY_TYPE
 * @property int $GAT_ACTIVATION_COUNT
 * @property bool $GAT_WAITING_FOR_START
 * @property string $GAT_DEFAULT_FLOW
 */
class Gateway extends Model implements FlowNodeInterface
{

    use ValidatingTrait, InitializeUidTrait, FlowNodeTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_GATEWAY';
    protected $primaryKey = 'GAT_UID';
    public $incrementing = false;
    public $timestamps = false;

    const TYPE = 'bpmnGateway';
    const TYPE_EMPTY = '';
    const TYPE_EXCLUSIVE = 'EXCLUSIVE';
    const TYPE_INCLUSIVE = 'INCLUSIVE';
    const TYPE_PARALLEL = 'PARALLEL';
    const TYPE_COMPLEX = 'COMPLEX';
    const DIRECTION_UNSPECIFIED = 'UNSPECIFIED';
    const DIRECTION_DIVERGING = 'DIVERGING';
    const DIRECTION_CONVERGING = 'CONVERGING';
    const DIRECTION_MIXED = 'MIXED';
    const EVENT_GATEWAY_TYPE_NONE = 'NONE';
    const EVENT_GATEWAY_TYPE_PARALLEL = 'PARALLEL';
    const EVENT_GATEWAY_TYPE_EXCLUSIVE = 'EXCLUSIVE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'GAT_UID',
        'GAT_NAME',
        'GAT_TYPE',
        'GAT_DIRECTION',
        'GAT_INSTANTIATE',
        'GAT_EVENT_GATEWAY_TYPE',
        'GAT_ACTIVATION_COUNT',
        'GAT_WAITING_FOR_START',
        'GAT_DEFAULT_FLOW'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'GAT_UID'                => '',
        'GAT_NAME'               => null,
        'GAT_TYPE'               => self::TYPE_EMPTY,
        'GAT_DIRECTION'          => self::DIRECTION_UNSPECIFIED,
        'GAT_INSTANTIATE'        => false,
        'GAT_EVENT_GATEWAY_TYPE' => self::EVENT_GATEWAY_TYPE_NONE,
        'GAT_ACTIVATION_COUNT'   => 0,
        'GAT_WAITING_FOR_START'  => true,
        'GAT_DEFAULT_FLOW'       => ''
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'GAT_UID'                => 'string',
        'GAT_NAME'               => 'string',
        'GAT_TYPE'               => 'string',
        'GAT_DIRECTION'          => 'string',
        'GAT_INSTANTIATE'        => 'bool',
        'GAT_EVENT_GATEWAY_TYPE' => 'string',
        'GAT_ACTIVATION_COUNT'   => 'int',
        'GAT_WAITING_FOR_START'  => 'bool',
        'GAT_DEFAULT_FLOW'       => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'GAT_UID'                => 'required|max:32',
        'GAT_NAME'               => 'nullable|max:255',
        'GAT_TYPE'               => 'in:' . self::TYPE_EMPTY . ',' . self::TYPE_EXCLUSIVE . ',' . self::TYPE_INCLUSIVE . ',' . self::TYPE_PARALLEL . ',' . self::TYPE_COMPLEX,
        'GAT_DIRECTION'          => 'in:' . self::DIRECTION_UNSPECIFIED . ',' . self::DIRECTION_DIVERGING . ',' . self::DIRECTION_CONVERGING . ',' . self::DIRECTION_MIXED,
        'GAT_EVENT_GATEWAY_TYPE' => 'in:' . self::EVENT_GATEWAY_TYPE_NONE . ',' . self::EVENT_GATEWAY_TYPE_PARALLEL . ',' . self::EVENT_GATEWAY_TYPE_EXCLUSIVE,
        'GAT_DEFAULT_FLOW'       => 'nullable|max:32',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'GAT_UID';
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
        return $this->morphOne(Shape::class, 'shape', 'BOU_ELEMENT_TYPE', 'ELEMENT_UID', 'GAT_UID');
    }
}
