<?php

namespace ProcessMaker\Traits;

trait HideSystemResources
{
    public function resolveRouteBinding($value)
    {
        $item = parent::resolveRouteBinding($value);

        $prefix = strtolower(substr(strrchr(get_class($item), '\\'), 1));
        $attribute = "{$prefix}_category_id";
        if ($item->$attribute && $item->category()->first()->is_system) {
            abort(404);
        } else if ($item->is_system) {
            abort(404);
        }

        return $item;
    }
}