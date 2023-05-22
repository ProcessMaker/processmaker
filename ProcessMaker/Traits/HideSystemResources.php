<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Str;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\User;

trait HideSystemResources
{
    public function resolveRouteBinding($value, $field = null)
    {
        $item = parent::resolveRouteBinding($value);

        if (request() && request()->is('api/*')) {
            // Allow the API to directly access hidden resources by ID
            return $item;
        }

        if (!$item) {
            abort(404);
        }

        $prefix = strtolower(substr(strrchr(get_class($item), '\\'), 1));
        $attribute = "{$prefix}_category_id";
        if ($item->$attribute && $item->category()->first()->is_system) {
            abort(404);
        } elseif ($item->is_system) {
            abort(404);
        }

        return $item;
    }

    public function scopeSystem($query)
    {
        if (substr(static::class, -8) === 'Category') {
            return $query->where('is_system', true);
        } else {
            return $query->whereHas('categories', function ($query) {
                $query->where('is_system', true);
            });
        }
    }

    public function scopeNonSystem($query)
    {
        if (substr(static::class, -8) === 'Category') {
            return $query->where('is_system', false);
        } elseif (static::class === Process::class) {
            $systemCategory = ProcessCategory::where('is_system', true)->pluck('id');

            return $query->whereNotIn('process_category_id', $systemCategory)->where('is_template', false);
        } elseif (static::class === Screen::class) {
            $systemCategory = ScreenCategory::where('is_system', true)->pluck('id');

            return $query->whereNotIn('screen_category_id', $systemCategory)->where('is_template', false);
        } elseif (static::class == ProcessRequest::class) {
            // ProcessRequests must be filtered this way since
            // they could be in a separate database
            $systemProcessIds = Process::withTrashed()->system()->pluck('id');
            $query->whereNotIn('process_id', $systemProcessIds);
        } elseif (static::class == User::class) {
            return $query->where('is_system', false);
        } elseif (static::class === ProcessRequestToken::class) {
            return $query->whereHas('process.categories', function ($query) {
                $query->where('is_system', false);
            });
        } elseif (static::class === ProcessTemplates::class) {
            return $query->where('process_templates.is_system', false);
        } else {
            return $query->whereDoesntHave('categories', function ($query) {
                $query->where('is_system', true);
            });
        }
    }
}
