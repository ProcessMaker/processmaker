<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\AnonymousUser;

class SetAnonymousUser
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
        if (!Auth::user()) {
            Auth::setUser(app(AnonymousUser::class));
        }

        return $next($request);
    }
}