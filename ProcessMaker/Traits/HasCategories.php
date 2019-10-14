<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\CategoryAssignment;
use App\Events\Relations\Attached;
use App\Events\Relations\Detached;
use App\Events\Relations\Syncing;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
        return $categories->using(CategoryAssignment::class);
    }
}
