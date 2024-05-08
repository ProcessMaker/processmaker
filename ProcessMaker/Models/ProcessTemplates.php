<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\ProcessTrait;

class ProcessTemplates extends Template
{
    use HasFactory;
    use HasCategories;
    use ProcessTrait;
    use HideSystemResources;

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
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }

    /**
     * Apply filters to the query based on the given filter string.
     */
    public function scopeWithFilters(Builder $query, $filter): void
    {
        $query->where(function ($query) use ($filter) {
            $query->where('process_templates.name', 'like', '%' . $filter . '%')
                ->orWhere('process_templates.description', 'like', '%' . $filter . '%')
                ->orWhere('user.firstname', 'like', '%' . $filter . '%')
                ->orWhere('user.lastname', 'like', '%' . $filter . '%')
                ->orWhereIn('process_templates.id', function ($qry) use ($filter) {
                    $qry->select('assignable_id')
                        ->from('category_assignments')
                        ->leftJoin('process_categories', function ($join) {
                            $join->on('process_categories.id', '=', 'category_assignments.category_id');
                            $join->where('category_assignments.category_type', '=', ProcessCategory::class);
                            $join->where('category_assignments.assignable_type', '=', ProcessTemplates::class);
                        })
                        ->where('process_categories.name', 'like', '%' . $filter . '%');
                });
        });
    }
}
