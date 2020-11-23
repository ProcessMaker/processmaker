<?php

namespace ProcessMaker\Http\Middleware;

use Closure;

class BrowserCache
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
        if (!env('BROWSER_CACHE', true)) {
            $response = $next($request);
            
            // from vendor/laravel/framework/src/Illuminate/Http/Middleware/SetCacheHeaders.php
            if (! $request->isMethodCacheable() || ! $response->getContent()) {
                return $response;
            }
            
            $response->header("pragma", "no-cache");
            $response->header("Cache-Control", "no-store");
            return $response;
        } else {
            return $next($request);
        }
    }
}
