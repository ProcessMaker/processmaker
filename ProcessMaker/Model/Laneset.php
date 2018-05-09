<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Process;
use Watson\Validating\ValidatingTrait;

/**
 * Container for one or more lanes.
 *
 * @property string $LNS_UID
 * @property int $process_id
 * @property string $LNS_NAME
 * @property string $LNS_PARENT_LANE
 * @property bool $LNS_IS_HORIZONTAL
 * @property string $LNS_STATE
 * @property Shape $shape
 */
class Laneset extends Model implements ElementInterface
{

    use ValidatingTrait, BaseElementTrait;

    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_LANESET';
    protected $primaryKey = 'LNS_UID';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * BPMN type
     */
    const TYPE = 'bpmnLaneset';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'LNS_UID',
        'process_id',
        'LNS_NAME',
        'LNS_PARENT_LANE',
        'LNS_IS_HORIZONTAL',
        'LNS_STATE'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'LNS_UID'           => '',
        'process_id'            => null,
        'LNS_NAME'          => null,
        'LNS_PARENT_LANE'   => null,
        'LNS_IS_HORIZONTAL' => true,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'LNS_UID'           => 'string',
        'process_id'            => 'int',
        'LNS_NAME'          => 'string',
        'LNS_PARENT_LANE'   => 'string',
        'LNS_IS_HORIZONTAL' => 'bool',
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'LNS_UID'         => 'required|max:32',
        'LNS_NAME'        => 'nullable|max:255',
        'LNS_PARENT_LANE' => 'nullable|max:32',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'LNS_UID';
    }

    /**
     * Owner process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
