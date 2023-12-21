<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class SessionControlKill
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $userSession = $request->session()->get('user_session');

        $configIP = Setting::configByKey('session-control.ip_restriction');

        if ($user->session && $configIP === '2') {
            $ip = $request->getClientIp() ?? $request->ip();

            if ($user->session->ip_address === $ip && $userSession !== $user->session->token) {
                session()->regenerate(true);
                session()->flush();

                return redirect()
                    ->route('login')
                    ->with('login-error', __('Your session has been killed'));
            }
        }

        return $next($request);
    }
}
