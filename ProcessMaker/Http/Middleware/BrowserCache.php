<?php

namespace ProcessMaker\Http\Middleware;

use Closure;

class BrowserCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (env('BROWSER_CACHE', true)) {
            return $next($request);
        }

        $response = $next($request);

        // from vendor/laravel/framework/src/Illuminate/Http/Middleware/SetCacheHeaders.php
        $shouldSetNoStore = $request->isMethodCacheable()
            && $response->getContent()
            && !$response->headers->has('ETag');

        if ($shouldSetNoStore) {
            $response->header('pragma', 'no-cache');
            $response->header('Cache-Control', 'no-store');
        }

        return $response;
    }
}
