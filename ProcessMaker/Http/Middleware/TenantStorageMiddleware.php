<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantStorageMiddleware
{
    public string $middleware = 'tenant.storage';

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app('currentTenant')) {
            return $next($request);
        }

        abort(404);
    }
}
