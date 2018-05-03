<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Process;
use Watson\Validating\ValidatingTrait;

/**
 * A flow defines a connection between two BPMN elements.
 *
 * @property string $FLO_UID
 * @property string $DIA_UID
 * @property string $FLO_TYPE
 * @property string $FLO_NAME
 * @property string $FLO_ELEMENT_ORIGIN
 * @property string $FLO_ELEMENT_ORIGIN_TYPE
 * @property int $FLO_ELEMENT_ORIGIN_PORT
 * @property string $FLO_ELEMENT_DEST
 * @property string $FLO_ELEMENT_DEST_TYPE
 * @property int $FLO_ELEMENT_DEST_PORT
 * @property bool $FLO_IS_INMEDIATE
 * @property string $FLO_CONDITION
 * @property int $FLO_X1
 * @property int $FLO_Y1
 * @property int $FLO_X2
 * @property int $FLO_Y2
 * @property string $FLO_STATE
 * @property int $FLO_POSITION
 */
class Flow extends Model
{

    use ValidatingTrait, InitializeUidTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_FLOW';
    protected $primaryKey = 'FLO_UID';
    public $incrementing = false;
    public $timestamps = false;

    const TYPE_DEFAULT = 'DEFAULT';
    const TYPE_SEQUENCE = 'SEQUENCE';
    const TYPE_MESSAGE = 'MESSAGE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'FLO_UID',
        'DIA_UID',
        'FLO_TYPE',
        'FLO_NAME',
        'FLO_ELEMENT_ORIGIN',
        'FLO_ELEMENT_ORIGIN_TYPE',
        'FLO_ELEMENT_ORIGIN_PORT',
        'FLO_ELEMENT_DEST',
        'FLO_ELEMENT_DEST_TYPE',
        'FLO_ELEMENT_DEST_PORT',
        'FLO_IS_INMEDIATE',
        'FLO_CONDITION',
        'FLO_X1',
        'FLO_Y1',
        'FLO_X2',
        'FLO_Y2',
        'FLO_STATE',
        'FLO_POSITION'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'FLO_UID'                 => '',
        'DIA_UID'                 => '',
        'FLO_TYPE'                => self::TYPE_SEQUENCE,
        'FLO_NAME'                => '',
        'FLO_ELEMENT_ORIGIN'      => '',
        'FLO_ELEMENT_ORIGIN_TYPE' => '',
        'FLO_ELEMENT_ORIGIN_PORT' => 0,
        'FLO_ELEMENT_DEST'        => '',
        'FLO_ELEMENT_DEST_TYPE'   => '',
        'FLO_ELEMENT_DEST_PORT'   => 0,
        'FLO_IS_INMEDIATE'        => false,
        'FLO_CONDITION'           => null,
        'FLO_X1'                  => 0,
        'FLO_Y1'                  => 0,
        'FLO_X2'                  => 0,
        'FLO_Y2'                  => 0,
        'FLO_STATE'               => null,
        'FLO_POSITION'            => 0
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'FLO_UID'                 => 'string',
        'DIA_UID'                 => 'string',
        'FLO_TYPE'                => 'string',
        'FLO_NAME'                => 'string',
        'FLO_ELEMENT_ORIGIN'      => 'string',
        'FLO_ELEMENT_ORIGIN_TYPE' => 'string',
        'FLO_ELEMENT_ORIGIN_PORT' => 'int',
        'FLO_ELEMENT_DEST'        => 'string',
        'FLO_ELEMENT_DEST_TYPE'   => 'string',
        'FLO_ELEMENT_DEST_PORT'   => 'int',
        'FLO_IS_INMEDIATE'        => 'bool',
        'FLO_CONDITION'           => 'string',
        'FLO_X1'                  => 'int',
        'FLO_Y1'                  => 'int',
        'FLO_X2'                  => 'int',
        'FLO_Y2'                  => 'int',
        'FLO_STATE'               => 'array',
        'FLO_POSITION'            => 'int'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'FLO_UID'                 => 'required|max:32',
        'DIA_UID'                 => 'required|max:32',
        'FLO_TYPE'                => 'required|in:' . self::TYPE_DEFAULT . ',' . self::TYPE_SEQUENCE . ',' . self::TYPE_MESSAGE,
        'FLO_NAME'                => 'nullable|max:255',
        'FLO_ELEMENT_ORIGIN'      => 'required|max:32',
        'FLO_ELEMENT_ORIGIN_TYPE' => 'required|max:32',
        'FLO_ELEMENT_ORIGIN_PORT' => 'required|numeric',
        'FLO_ELEMENT_DEST'        => 'required|max:32',
        'FLO_ELEMENT_DEST_TYPE'   => 'required|max:32',
        'FLO_ELEMENT_DEST_PORT'   => 'required|numeric',
        'FLO_CONDITION'           => 'nullable|max:512',
        'FLO_X1'                  => 'required|numeric',
        'FLO_Y1'                  => 'required|numeric',
        'FLO_X2'                  => 'required|numeric',
        'FLO_Y2'                  => 'required|numeric',
        'FLO_POSITION'            => 'required|numeric',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'FLO_UID';
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
     * Set the source node of the flow.
     *
     * @param \ProcessMaker\Model\FlowNodeInterface $source
     *
     * @return $this
     */
    public function setSourceAttribute(FlowNodeInterface $source)
    {
        $this->FLO_ELEMENT_ORIGIN = $source->getKey();
        $this->FLO_ELEMENT_ORIGIN_TYPE = $source->getMorphClass();
        return $this;
    }

    /**
     * Source node of the flow.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function source()
    {
        return $this->morphTo('incoming', 'FLO_ELEMENT_ORIGIN_TYPE', 'FLO_ELEMENT_ORIGIN');
    }

    /**
     * Set the target node of the flow.
     *
     * @param \ProcessMaker\Model\FlowNodeInterface $target
     *
     * @return $this
     */
    public function setTargetAttribute(FlowNodeInterface $target)
    {
        $this->FLO_ELEMENT_DEST = $target->getKey();
        $this->FLO_ELEMENT_DEST_TYPE = $target->getMorphClass();
        return $this;
    }

    /**
     * Target node of the flow.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function target()
    {
        return $this->morphTo('outgoing', 'FLO_ELEMENT_DEST_TYPE', 'FLO_ELEMENT_DEST');
    }
}
