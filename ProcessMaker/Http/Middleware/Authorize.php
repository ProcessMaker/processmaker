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

        // Remove the api route prefix since they will have the
        // same permissions as the web routes.
        $permission = preg_replace('/^api\./', '', $permission);

        if ($request->user()->hasPermission($permission)) {
            return $next($request);
        } elseif ($this->allowIndexForShow($permission, $request)) {
            return $next($request);
        } else {
            throw new AuthorizationException("Not authorized: " . $permission);
        }
    }

    /**
     * If the user has show permission, assume they
     * have index/list permission as well.
     */
    private function allowIndexForShow($permission, $request)
    {
        if(preg_match('/^(.*)\.index$/', $permission, $match)) {
            return $request->user()->hasPermission($match[1] . '.show');
        }
        return false;
    }
}
