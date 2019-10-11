<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\CategoryAssignment;

trait HasCategories
{
    public function assignable()
    {
        return $this->morphedByMany(CategoryAssignment::class, 'assignable');
    }

    public function category()
    {
        return $this->hasOneThrough(static::categoryClass, CategoryAssignment::class, 'assignable_id', 'id', null, 'category_id')->where('assignable_type', static::class);
    }

    public function categories()
    {
        $categories = $this->morphedByMany(static::categoryClass, 'category', 'category_assignments', 'assignable_id', 'category_id');
        $categories->withPivotValue('assignable_type', static::class);
        return $categories;
    }
}
