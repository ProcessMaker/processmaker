<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Shape related to an element of the process.
 *
 * @property string $BOU_UID
 * @property string $DIA_UID
 * @property string $ELEMENT_UID
 * @property string $BOU_ELEMENT
 * @property string $BOU_ELEMENT_TYPE
 * @property int $BOU_X
 * @property int $BOU_Y
 * @property int $BOU_WIDTH
 * @property int $BOU_HEIGHT
 * @property int $BOU_REL_POSITION
 * @property int $BOU_SIZE_IDENTICAL
 * @property string $BOU_CONTAINER
 * @property int $process_id
 * @property Diagram $diagram
 */
class Shape extends Model
{

    use ValidatingTrait, InitializeUidTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_BOUND';
    protected $primaryKey = 'BOU_UID';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'BOU_UID',
        'DIA_UID',
        'ELEMENT_UID',
        'BOU_ELEMENT',
        'BOU_ELEMENT_TYPE',
        'BOU_X',
        'BOU_Y',
        'BOU_WIDTH',
        'BOU_HEIGHT',
        'BOU_REL_POSITION',
        'BOU_SIZE_IDENTICAL',
        'BOU_CONTAINER',
        'process_id'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'BOU_UID'            => '',
        'DIA_UID'            => '',
        'ELEMENT_UID'        => '',
        'BOU_ELEMENT'        => '',
        'BOU_ELEMENT_TYPE'   => '',
        'BOU_X'              => 0,
        'BOU_Y'              => 0,
        'BOU_WIDTH'          => 0,
        'BOU_HEIGHT'         => 0,
        'BOU_REL_POSITION'   => 0,
        'BOU_SIZE_IDENTICAL' => 0,
        'BOU_CONTAINER'      => '',
        'process_id'             => NULL
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'BOU_UID'            => 'string',
        'DIA_UID'            => 'string',
        'ELEMENT_UID'        => 'string',
        'BOU_ELEMENT'        => 'string',
        'BOU_ELEMENT_TYPE'   => 'string',
        'BOU_X'              => 'int',
        'BOU_Y'              => 'int',
        'BOU_WIDTH'          => 'int',
        'BOU_HEIGHT'         => 'int',
        'BOU_REL_POSITION'   => 'int',
        'BOU_SIZE_IDENTICAL' => 'int',
        'BOU_CONTAINER'      => 'string',
        'process_id'             => 'int'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'BOU_UID'            => 'required|max:32',
        'DIA_UID'            => 'required|max:32',
        'ELEMENT_UID'        => 'nullable|max:32',
        'BOU_ELEMENT'        => 'nullable|max:32',
        'BOU_ELEMENT_TYPE'   => 'required|max:32',
        'BOU_X'              => 'numeric',
        'BOU_Y'              => 'numeric',
        'BOU_WIDTH'          => 'numeric',
        'BOU_HEIGHT'         => 'numeric',
        'BOU_REL_POSITION'   => 'numeric',
        'BOU_SIZE_IDENTICAL' => 'numeric',
        'BOU_CONTAINER'      => 'nullable|max:30',
        'process_id'             => 'required'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'BOU_UID';
    }

    /**
     * Diagram owner of the shape.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function diagram()
    {
        return $this->belongsTo(Diagram::class, 'DIA_UID', 'DIA_UID');
    }

    /**
     * Set the element related to the shape.
     */
    public function setElementAttribute($element)
    {
        $this->ELEMENT_UID = $element->getKey();
        $this->BOU_ELEMENT_TYPE = $element->getMorphClass();
    }

    /**
     * Element related to the shape.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function element()
    {
        return $this->morphTo('element', 'BOU_ELEMENT_TYPE', 'ELEMENT_UID');
    }

    /**
     * Set the container related to the shape.
     */
    public function setContainerAttribute($container)
    {
        $this->BOU_ELEMENT = $container->getKey();
        $this->BOU_CONTAINER = $container->getMorphClass();
    }

    /**
     * Container of the shape.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function container()
    {
        return $this->this->morphTo('container', 'BOU_CONTAINER', 'BOU_ELEMENT');
    }
}
