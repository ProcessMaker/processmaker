<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Contracts\ProcessModelInterface;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasSelfServiceTasks;
use ProcessMaker\Traits\ProcessTrait;

/**
 * ProcessVersion is used to store the historical version of a process.
 *
 * @property string id
 * @property string bpmn
 * @property string name
 * @property string process_category_id
 * @property string process_id
 * @property string status
 * @property string start_events
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessVersion extends Model implements ProcessModelInterface
{
    use HasSelfServiceTasks;
    use HasCategories;
    use ProcessTrait;

    const categoryClass = ProcessCategory::class;

    protected $connection = 'processmaker';

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
     */
    protected $guarded = [
        'id',
        'updated_at',
    ];

    protected $casts = [
        'start_events' => 'array',
        'warnings' => 'array',
        'self_service_tasks' => 'array',
        'signal_events' => 'array',
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
     * Set multiple|single categories to the process
     *
     * @param string $value
     */
    public function setProcessCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'process_category_id');
    }

    /**
     * The process to which belongs this version
     *
     * @return Process
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Get the associated process
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Process::class, 'process_id', 'id');
    }
}
