<?php

namespace ProcessMaker\Http\Middleware;

use Closure;

class CacheControlMiddleware
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
        $response = $next($request);

        if ($request->isJson() || $request->expectsJson()) {
            $response->header('Cache-Control', 'no-store');
        }

        return $response;
    }
}
