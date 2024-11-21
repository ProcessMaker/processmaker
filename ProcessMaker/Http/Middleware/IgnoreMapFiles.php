<?php

namespace ProcessMaker\Http\Middleware;

use Closure;

class IgnoreMapFiles
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
        // Check if the request is for a .map file (this means it does not exist in public folder)
        if ($request->is('*.map')) {
            // Return a 204 No Content response to avoid use it as processmaker_intended url
            return response('', 204);
        }

        // Continue with the request if it's not a .map file
        return $next($request);
    }
}
