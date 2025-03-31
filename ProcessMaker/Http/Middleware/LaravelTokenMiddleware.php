<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;

class LaravelTokenMiddleware
{
    /**
     * Laravel Passport enables cookie authentication (JWT)
     * therefore, the request is validated when the cookie and
     * X-CSRF-TOKEN are present, so that its cache permissions are also configured.
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
