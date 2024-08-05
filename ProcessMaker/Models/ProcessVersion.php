<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
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
 * @property string alternative
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class ProcessVersion extends ProcessMakerModel implements ProcessModelInterface
{
    use HasSelfServiceTasks;
    use HasCategories;
    use ProcessTrait;

    const categoryClass = ProcessCategory::class;

    protected $connection = 'processmaker';

    /**
     * Attributes that are not mass assignable.
     *
     * @var array
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
        'conditional_events' => 'array',
        'properties' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
        'bpmn',
        'svg',
    ];

    protected static function boot()
    {
        static::saved(static function (self $processVersion) {
            $processVersion->saveProcessableVersions();
        });

        parent::boot();
    }

    /**
     * Ensures there is a matching processable for each process version
     *
     * @return void
     */
    protected function saveProcessableVersions()
    {
        $processables = [
            'usersCanCancel' => 'CANCEL',
            'usersCanEditData' => 'EDIT_DATA',
            'groupsCanCancel' => 'CANCEL',
            'groupsCanEditData' => 'EDIT_DATA',
        ];

        foreach ($processables as $relationshipName => $methodName) {
            if (!$this->process->$relationshipName()->exists()) {
                continue;
            }

            $includeWithPivot = [
                'process_id' => $this->process->id,
                'method' => $methodName,
            ];

            $updateWith = $this->process->$relationshipName->keyBy('id')->map(
                function ($model) use ($includeWithPivot) {
                    return $includeWithPivot;
                }
            );

            $this->$relationshipName()->sync($updateWith->toArray(), false);
        }
    }

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

    /**
     * Scope to only return draft versions.
     */
    public function scopeDraft(Builder $query)
    {
        return $query->where('draft', true);
    }

    /**
     * Scope to only return published versions.
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('draft', false);
    }
}
