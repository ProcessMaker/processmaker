<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\Response;

class SessionControlBlock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasUserSession($request)) {
            return $next($request);
        }

        $user = $this->getUserWithSession($request->input('username'));

        if ($user && $this->blockDuplicateSessionByIp($user, $request)) {
            return redirect()
                ->route('login')
                ->with('login-error', __('You are already logged in'));
        }

        return $next($request);
    }

    private function hasUserSession(Request $request): bool
    {
        return $request->session()->has('user_session');
    }

    private function getUserWithSession(string $username): ?User
    {
        return User::with(['session'])->where('username', $username)->first();
    }

    private function blockDuplicateSessionByIp(User $user, Request $request): bool
    {
        if ($user->session && Setting::configByKey('session-control.ip_restriction') === '1') {
            $ip = $request->getClientIp() ?? $request->ip();
            return $user->session->ip_address === $ip;
        }

        return false;
    }
}
