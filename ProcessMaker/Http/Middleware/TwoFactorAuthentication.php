<?php

namespace ProcessMaker\Http\Middleware;

use Closure;

class TwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Only validate if 2FA is enabled and one send method was defined
        if (config('password-policies.2fa_enabled', false) &&
            !empty(config('password-policies.2fa_method', []))) {
            // Check if current session have the 2FA validated
            if (!session()->get('2fa-validated', false)) {
                // If not validated display the 2FA code screen
                return redirect()->route('2fa');
            }
        }

        return $next($request);
    }
}
