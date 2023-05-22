<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryAssignment extends Pivot
{
    public function setMorphType($type)
    {
        return $this;
    }

    public function setMorphClass($class)
    {
        return $this;
    }

    public static function boot()
    {
        parent::boot();
        static::saved(function ($pivot) {
            switch ($pivot->assignable_type) {
                case Process::class:
                    //$pivot->assignable->process_category_id = $pivot->category->getKey();
                    break;
                case Script::class:
                    //$pivot->assignable->script_category_id = $pivot->category->getKey();
                    break;
                case Screen::class:
                    //$pivot->assignable->screen_category_id = $pivot->category->getKey();
                    break;
            }
        });
    }

    public function assignable()
    {
        return $this->morphTo('assignable');
    }

    public function category()
    {
        return $this->morphTo('category');
    }
}
