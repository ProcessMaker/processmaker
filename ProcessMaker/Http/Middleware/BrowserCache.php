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
        if (env('BROWSER_CACHE', true)) {
            return $next($request);
        }

        $response = $next($request);

        $cacheControl = strtolower((string) $response->headers->get('Cache-Control', ''));

        // from vendor/laravel/framework/src/Illuminate/Http/Middleware/SetCacheHeaders.php
        $shouldSetNoStore = $request->isMethodCacheable()
            && $response->getContent()
            && !(str_contains($cacheControl, 'private') && str_contains($cacheControl, 'must-revalidate'));

        if ($shouldSetNoStore) {
            if (method_exists($response, 'setCache')) {
                $response->setCache([
                    'no_store' => true,
                    'no_cache' => true,
                    'private' => false,
                ]);
            }

            $response->headers->set('pragma', 'no-cache', false);
        }

        return $response;
    }
}
