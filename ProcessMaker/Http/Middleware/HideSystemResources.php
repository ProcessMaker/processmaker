<?php

namespace ProcessMaker\Http\Middleware;

use Closure;

class HideSystemResources
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        foreach ($request->route()->parameters() as $item) {
            if (!is_object($item)) {
                continue;
            }

            if (!array_key_exists('ProcessMaker\Traits\HideSystemResources', class_uses($item))) {
                continue;
            }

            $prefix = strtolower(substr(strrchr(get_class($item), '\\'), 1));
            $attribute = "{$prefix}_category_id";
            if ($item->$attribute && $item->category()->first()->is_system) {
                abort(404);
            } else if ($item->is_system) {
                abort(404);
            }
        }

        return $next($request);
    }
}
