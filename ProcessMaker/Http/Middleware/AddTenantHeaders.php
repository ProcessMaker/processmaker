<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;

class AddTenantHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $tenant = app('currentTenant');

        if ($tenant) {
            $response->headers->set('x-tenant-id', $tenant->id);
            $response->headers->set('x-tenant-name', $tenant->name);
        } else {
            $response->headers->set('x-tenant-id', 'none');
            $response->headers->set('x-tenant-name', 'No tenant');
        }

        return $response;
    }
}
