<?php
namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use \Illuminate\Auth\Access\AuthorizationException;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        // At this point we should have already checked if the
        // user is logged in so we can assume $request->user()
        $permission = $request->route()->action['as'];
        if ($request->user()->hasPermission($permission)) {
            return $next($request);
        } elseif ($permission == 'processes.index'){ // && $request->user()->hasPermission('processes.show')) {
            return $next($request);
        } else {
            throw new AuthorizationException("Not authorized: " . $permission);
        }
    }
}
