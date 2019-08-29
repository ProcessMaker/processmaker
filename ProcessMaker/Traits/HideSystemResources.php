<?php

namespace ProcessMaker\Traits;
use Illuminate\Support\Str;

trait HideSystemResources
{
    public function resolveRouteBinding($value)
    {
        $item = parent::resolveRouteBinding($value);

        if (!$item) {
            abort(404);
        }

        $prefix = strtolower(substr(strrchr(get_class($item), '\\'), 1));
        $attribute = "{$prefix}_category_id";
        if ($item->$attribute && $item->category()->first()->is_system) {
            abort(404);
        } else if ($item->is_system) {
            abort(404);
        }

        return $item;
    }

    public function scopeNonSystem($query)
    {
        if (substr(static::class, -8) === 'Category') {
            return $query->where('is_system', false);
        } else {
            $prefix = Str::singular($query->getModel()->getTable());
            return $query
                ->where($prefix . '_category_id', null)
                ->orWhereHas('category', function($q){
                    $q->where('is_system', false);
                });
        }
    }
}