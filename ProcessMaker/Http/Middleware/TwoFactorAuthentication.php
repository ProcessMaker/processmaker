<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use ProcessMaker\Http\Controllers\Auth\TwoFactorAuthController;

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
        // Check if 2FA is enabled, a method is set, and the current session is not yet validated
        if (config('password-policies.2fa_enabled', false) &&
            !empty(config('password-policies.2fa_method', [])) &&
            !session()->get(TwoFactorAuthController::TFA_VALIDATED, false) &&
            TwoFactorAuthController::check2faByGroups() &&
            session('sso_driver') === null
        ) {
            // If not validated display the 2FA code screen
            return redirect()->route('2fa');
        }

        return $next($request);
    }
}
