<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerifyChangePasswordNeeded
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
        if ($this->checkForForceChangePassword()) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }

    public function checkForForceChangePassword()
    {
        return Auth::user() && Auth::user()->force_change_password;
    }
}
