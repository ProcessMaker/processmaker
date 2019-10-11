<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\CategoryAssignment;

trait HasCategories
{
    public function assignable()
    {
        return $this->morphedByMany(CategoryAssignment::class, 'assignable');
    }

    public function categories()
    {
        $categories = $this->morphedByMany(static::categoryClass, 'category', 'category_assignments', 'assignable_id', 'category_id');
        $categories->withPivotValue('assignable_type', static::class);
        return $categories;
    }

    public static function bootHasCategories()
    {
        static::saved(function ($model) {
            $model->categories()->syncWithoutDetaching([$model->category->getKey()]);
        });
    }
}
