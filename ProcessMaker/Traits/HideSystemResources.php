<?php

namespace ProcessMaker\Traits;

trait HideSystemResources
{
    public function resolveRouteBinding($value)
    {
        $item = parent::resolveRouteBinding($value);

        if ($item && $item->process_category_id && $item->category()->first()->is_system) {
            abort(404);
        } else if ($item && $item->is_system) {
            abort(404);
        }

        return $item;
    }
}