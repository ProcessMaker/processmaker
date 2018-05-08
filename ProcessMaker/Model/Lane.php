<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Process;
use Watson\Validating\ValidatingTrait;

/**
 * Class to organize and categorize the activities.
 *
 * @property string $LAN_UID
 * @property int $process_id
 * @property string $LNS_UID
 * @property string $LAN_NAME
 * @property string $LAN_CHILD_LANESET
 * @property bool $LAN_IS_HORIZONTAL
 * @property Process $process
 * @property Laneset $laneset
 * @property Shape $shape
 */
class Lane extends Model implements ElementInterface
{

    use ValidatingTrait, BaseElementTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_LANE';
    protected $primaryKey = 'LAN_UID';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * BPMN type
     */
    const TYPE = 'bpmnLane';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'LAN_UID',
        'process_id',
        'LNS_UID',
        'LAN_NAME',
        'LAN_CHILD_LANESET',
        'LAN_IS_HORIZONTAL'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'LAN_UID'           => '',
        'process_id'            => null,
        'LNS_UID'           => null,
        'LAN_NAME'          => null,
        'LAN_CHILD_LANESET' => null,
        'LAN_IS_HORIZONTAL' => true
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'LAN_UID'           => 'string',
        'process_id'            => 'int',
        'LNS_UID'           => 'string',
        'LAN_NAME'          => 'string',
        'LAN_CHILD_LANESET' => 'string',
        'LAN_IS_HORIZONTAL' => 'bool'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'LAN_UID'           => 'required|max:32',
        'LNS_UID'           => 'required|max:32',
        'LAN_NAME'          => 'nullable|max:255',
        'LAN_CHILD_LANESET' => 'nullable|max:32',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'LAN_UID';
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

    /**
     * Owner laneset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function laneset()
    {
        return $this->belongsTo(Laneset::class, 'LNS_UID', 'LNS_UID');
    }
}
