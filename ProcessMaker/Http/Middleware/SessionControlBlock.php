<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
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
        $hasUserSession = $request->session()->has('user_session');

        if ($user && !$hasUserSession) {
            // Check if the user's session should be blocked based on IP restriction
            if ($this->blockedByIp($user, $request)) {
                return $this->redirectToLogin();
            }
            // Check if the user's session should be blocked based on device restriction
            if ($this->blockedByDevice($user, $request)) {
                return $this->redirectToLogin();
            }
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

    private function blockedByIp(User $user, Request $request): bool
    {
        return Setting::configByKey(self::IP_RESTRICTION_KEY) === '1' && $this->blockSessionByIp($user, $request);
    }

    private function blockedByDevice(User $user, Request $request): bool
    {
        return Setting::configByKey(self::DEVICE_RESTRICTION_KEY) === '1'
            && $this->blockSessionByDevice($user, $request);
    }

    /**
     * Checks if a user's session is a duplicate based on their IP address.
     *
     * @param User user
     * @param Request request
     *
     * @return bool
     */
    private function blockSessionByIp(User $user, Request $request): bool
    {
        // Get the user's most recent session
        $session = $user->sessions->sortByDesc('created_at')->first();
        // Get the user's current IP address
        $ip = $request->getClientIp() ?? $request->ip();

        return $session->ip_address === $ip;
    }

    /**
     * Checks if the user's current session device matches the device used in the request
     *
     * @param User user
     * @param Request request
     *
     * @return bool
     */
    private function blockSessionByDevice(User $user, Request $request): bool
    {
        $agent = new Agent();
        // Get the device details from the request
        $agentDevice = $agent->device() ? $agent->device() : 'Unknown';
        $requestDevice = $this->formatDeviceInfo($agentDevice, $agent->deviceType(), $agent->platform());
        // Get the user's current IP address
        $ip = $request->getClientIp() ?? $request->ip();
        // Get the active user sessions
        $sessions = $user->sessions()
            ->where([
                ['is_active', true],
                ['expired_date', null],
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $openSessions = $sessions->reduce(function ($carry, $session) use ($requestDevice, $ip) {
            $sessionDevice = $this->formatDeviceInfo(
                $session->device_name, $session->device_type, $session->device_platform
            );

            if ($requestDevice !== $sessionDevice || $session->ip_address !== $ip) {
                return $carry + 1;
            } else {
                return $carry - 1;
            }
        }, 0);

        return $openSessions > 0;
    }

    private function formatDeviceInfo(string $deviceName, string $deviceType, string $devicePlatform): string
    {
        return Str::slug($deviceName . '-' . $deviceType . '-' . $devicePlatform);
    }

    private function redirectToLogin(): Response
    {
        return redirect()
            ->route('login')
            ->with('login-error', __('You are already logged in'));
    }
}
