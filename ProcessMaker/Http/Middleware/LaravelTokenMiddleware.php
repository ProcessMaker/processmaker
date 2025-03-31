<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;

class LaravelTokenMiddleware
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
        $userId = auth()->id();

        $permissions = Cache::get("user_{$userId}_permissions");

        if ($request->hasCookie(Passport::cookie()) && $request->hasHeader('X-CSRF-TOKEN') && !$permissions) {
            // the user is authenticated but the permissions are not set
            // we need to return a 401 error
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return $response;
    }
}
