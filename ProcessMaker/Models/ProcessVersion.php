<?php

namespace ProcessMaker\Models;

use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Enums\ActiveType;

/**
 * ProcessVersion is used to store the historical version of a process.
 *
 * @property string id
 * @property string bpmn
 * @property string name
 * @property string process_category_id
 * @property string process_id
 * @property string status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessVersion extends Model
{
    /**
     * The model's attributes and default values
     *
     * @var array
     */
    protected $attributes = [
        'status' => ActiveType::ACTIVE
    ];

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
     */
    protected $guarded = [
        'id',
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
    protected $ids = [
        'process_category_id',
        'process_id',
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
            'status' => [new EnumValue(ActiveType::class)],
            'process_category_id' => 'exists:process_categories,id',
            'process_id' => 'exists:processes,id',
        ];
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function processCategory()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_id');
    }

}
