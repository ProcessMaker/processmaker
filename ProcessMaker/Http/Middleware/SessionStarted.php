<?php

namespace ProcessMaker\Http\Middleware;

use Auth;
use Closure;
use Session;
use Carbon\Carbon;
use ProcessMaker\Events\SessionStarted as SessionStartedEvent;

class SessionStarted
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
        if (Auth::check()) {
            event(new SessionStartedEvent(Auth::user()));
        }

        //Store in the session the rememberme token status so it is accesible in layout.blade
        Session::put('rememberme', $this->userHasValidRememberMe($request));

        return $next($request);
    }

    /**
     *  Validates if the remember me token stored in the User table is tha same with the one stored in the cookie
     * @param  \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    private function userHasValidRememberMe($request)
    {

        if (\Auth::user() === null) {
            return false;
        }

        $guard = \Auth::guard();

        // Remember me is validate only in user session guards
        if (!is_a($guard, \Illuminate\Auth\SessionGuard::class)) {
            return false;
        }

        // recallerName is the name of the cookie that stores the remember me token
        $recallerName = $guard->getRecallerName();

        // Get the sections of the cookie
        $parts = explode('|', $request->cookies->get($recallerName));

        // If the cookie doesn't have the correct parts the remember me token is invalid
        if (count($parts) < 2) {
            return false;
        }

        $token = $parts[1];

        if ($token === null || $token === '') {
            return false;
        }

        // Validate the cookie's remember me token with the one stored in the database
        if (hash_equals(\Auth::user()->getRememberToken(), $token)) {
            return true;
        }

        return false;
    }
}
