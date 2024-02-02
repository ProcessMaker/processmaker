<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use ProcessMaker\Models\User;
use ProcessMaker\Events\SessionStarted as SessionStartedEvent;
use ProcessMaker\Facades\RequestDevice;

class SessionStarted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Cache the session id if this is a new session
        if (!$new = Cache::has($session = Session::getId())) {
            Cache::put($session, true, (int) (config('session.lifetime') ?? 60));
        }

        // If this is a new session and the user is logged
        // in, fire the SessionStartedEvent
        if (!$new && $user = Auth::check() ? Auth::user() : false) {
            event(new SessionStartedEvent($user));
        }

        // Store the remember me token status in the session
        // data to make it accessible in layout.blade
        Session::put('rememberme', $this->userHasValidRememberMe($request));

        Cookie::queue(
            RequestDevice::getVariableName(),
            RequestDevice::getId(),
            config('session.lifetime'),
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            false
        );

        return $next($request);
    }

    /**
     * Validates the "remember me" token stored in the user table matches
     * the one stored in the user's session
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    private function userHasValidRememberMe(Request $request): bool
    {
        if (!Auth::user() instanceof User) {
            return false;
        }

        // Remember me is validated only in guarded sessions
        if (!is_a($guard = Auth::guard(), SessionGuard::class)) {
            return false;
        }

        // $recaller_name is the name of the cookie that stores the remember me token
        $recaller_name = $guard->getRecallerName();

        // Get the sections of the cookie
        $parts = explode('|', $request->cookies->get($recaller_name));

        // If the cookie doesn't have the correct parts the remember me token is invalid
        if (count($parts) >= 2) {
            $token = $parts[1];

            // Validate the cookie's remember me token with the one stored in the database
            if ($token !== null && $token !== '') {
                return hash_equals(Auth::user()->getRememberToken(), $token);
            }
        }

        return false;
    }
}
