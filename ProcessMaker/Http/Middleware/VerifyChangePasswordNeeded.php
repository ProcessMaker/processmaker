<?php

namespace ProcessMaker\Http\Middleware;

use Carbon\Carbon;
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

        if ($this->checkPasswordExpiration()) {
            // Set the error message
            session()->put('login-error', _('Your password has expired.'));

            // Redirect to change password screen
            return redirect()->route('password.change');
        }

        return $next($request);
    }

    public function checkForForceChangePassword()
    {
        return Auth::user() && Auth::user()->force_change_password;
    }

    public function checkPasswordExpiration()
    {
        $validationRequired = config('password-policies.expiration_days', false) &&
            Auth::user() && !empty(Auth::user()->password_changed_at);

        return $validationRequired &&
            (Carbon::now()->diffInDays(Auth::user()->password_changed_at) >=
                config('password-policies.expiration_days'));
    }
}
