<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * ProcessVersion is used to store the historical version of a process.
 *
 * @property string uuid
 * @property string bpmn
 * @property string name
 * @property string process_category_uuid
 * @property string process_uuid
 * @property string status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessVersion extends Model
{
    use HasBinaryUuid;

    public $incrementing = false;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
     */
    protected $guarded = [
        'uuid',
        'bpmn',
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
    protected $uuids = [
        'process_category_uuid',
        'process_uuid',
    ];

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'name' => 'required',
            'status' => 'in:ACTIVE,INACTIVE',
            'process_category_uuid' => 'exists:process_categories,uuid',
            'process_uuid' => 'exists:processes,uuid',
        ];
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_uuid');
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function processCategory()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_uuid');
    }

}