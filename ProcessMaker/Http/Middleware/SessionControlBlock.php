<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use ProcessMaker\Managers\SessionControlManager;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\Response;

class SessionControlBlock
{
    const IP_RESTRICTION_KEY = 'session-control.ip_restriction';

    const DEVICE_RESTRICTION_KEY = 'session-control.device_restriction';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->getUser($request);
        $ip = $request->getClientIp() ?? $request->ip();
        $hasUserSession = $request->session()->has('user_session');

        $sessionManager = new SessionControlManager($user, $ip);

        if ($user && !$hasUserSession && $sessionManager->isSessionBlocked()) {
            return $sessionManager->redirectToLogin();
        }

        return $next($request);
    }

    private function getUser(Request $request): ?User
    {
        return User::with(['sessions' => function ($query) {
            $query->where('is_active', true);
        }])
            ->whereHas('sessions', function (Builder $query) {
                $query->where('is_active', true);
            })->where('username', $request->input('username'))
            ->first();
    }
}
