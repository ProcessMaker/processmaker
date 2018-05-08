<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 *
 * @property string $DIA_UID
 * @property string $DIA_NAME
 * @property int $DIA_IS_CLOSABLE
 * @property int $process_id
 */
class Diagram extends Model
{

    use ValidatingTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_DIAGRAM';
    protected $primaryKey = 'DIA_UID';
    public $timestamps = false;
    public $incrementing = false;

    const TYPE = 'bpmnDiagram';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'DIA_UID',
        'DIA_NAME',
        'DIA_IS_CLOSABLE',
        'process_id'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'DIA_UID'         => '',
        'DIA_NAME'        => null,
        'DIA_IS_CLOSABLE' => false,
        'process_id'          => null
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'DIA_UID'         => 'string',
        'DIA_NAME'        => 'string',
        'DIA_IS_CLOSABLE' => 'bool',
        'process_id'          => 'int'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'DIA_UID'  => 'required|max:32',
        'DIA_NAME' => 'nullable|max:255',
        'process_id'   => 'required'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'DIA_UID';
    }

    /**
     * Process owner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Activities bounded by the diagram.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function activities()
    {
        return $this->process->activities()->whereHas('shape', function ($query) {
            $query->where('DIA_UID', $this->DIA_UID);
        });
    }

    /**
     * Events bounded by the diagram.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function events()
    {
        return $this->process->events()->whereHas('shape', function ($query) {
            $query->where('DIA_UID', $this->DIA_UID);
        });
    }

    /**
     * Gateways bounded by the diagram.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function gateways()
    {
        return $this->process->gateways()->whereHas('shape', function ($query) {
            $query->where('DIA_UID', $this->DIA_UID);
        });
    }

    /**
     * Flows bounded by the diagram.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function flows()
    {
        return $this->process->flows()->where('DIA_UID', $this->DIA_UID);
    }

    /**
     * Shapes of the diagram
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shapes()
    {
        return $this->hasMany(Shape::class, "DIA_UID", "DIA_UID");
    }

    /**
     * Artifacts bounded by the diagram.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function artifacts()
    {
        return $this->process->artifacts();
    }

    /**
     * Create a flow from a source element to a target element.
     *
     * @param \ProcessMaker\Model\FlowNodeInterface $source
     * @param \ProcessMaker\Model\FlowNodeInterface $target
     * @param array $options
     *
     * @return Flow
     */
    public function createFlow(FlowNodeInterface $source, FlowNodeInterface $target, array $options = [])
    {
        $flow = $this->flows()->make($options);
        $flow->DIA_UID = $this->DIA_UID;
        $flow->source = $source;
        $flow->target = $target;
        $flow->save();
        return $flow;
    }

    /**
     * Create a shape for the element.
     *
     * @param \ProcessMaker\Model\ElementInterface $element
     * @param array $options
     *
     * @return Shape
     */
    public function createShape(ElementInterface $element, array $options = [])
    {
        $shape = $this->shapes()->make($options);
        $shape->process_id = $this->process_id;
        $shape->element = $element;
        $shape->container = $this;
        $shape->save();
        return $shape;
    }
}
