<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Template;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\ProcessTrait;

class ProcessTemplates extends Template
{
    use HasFactory;
    use HasCategories;
    use ProcessTrait;

    protected $table = 'process_templates';

    const categoryClass = ProcessCategory::class;

    public $process_category_id;

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_id')->withDefault();
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
     * Get multiple|single categories of the process
     *
     * @param string $value
     */
    public function getProcessCategoryIdAttribute($value)
    {
        return implode(',', $this->categories->pluck('category_id')->toArray()) ?: $value;
    }
}
