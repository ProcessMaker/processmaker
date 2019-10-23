<?php

namespace ProcessMaker\Traits;
use Illuminate\Support\Str;
use ProcessMaker\Models\ProcessRequest;

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
        if (
            method_exists($item, 'process') &&
            $item->process()->first()->category()->count() > 0 &&
            $item->process()->first()->category()->first()->is_system
        ) {
            abort(404);

        } else if (
            $item->$attribute &&
            $item->category()->first()->is_system
        ) {
            abort(404);
            
        } else if ($item->is_system) {
            abort(404);
        }

        return $item;
    }

    public function isSystemResource()
    {
        if (self::class === ProcessRequest::class) {
            if (
                $this->process()->first()->category()->count() > 0 &&
                $this->process()->first()->category()->first()->is_system
            ) {
                return true;
            }
            return false;

        } elseif (substr(self::class, -8) === 'Category') {
            return $this->is_system;

        } else {
            if (
                $this->category()->count() > 0 &&
                $this->category()->first()->is_system
            ) {
                return true;
            }
            return false;
        }
    }

    public function scopeNonSystem($query)
    {
        // Note that ProcessRequests can not be filtered with
        // scopes because they live on a different database server
        if (substr(self::class, -8) === 'Category') {
            return $query->where('is_system', false);
        } else {
            return $query->whereDoesntHave('categories', function ($query) {
                $query->where('is_system', true);
            });
        }
    }
}
