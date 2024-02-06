<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Models\UserSession;
use Symfony\Component\HttpFoundation\Response;

class SessionControlKill
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
        if (Auth::check()) {
            $user = Auth::user();
            $userSession = $request->session()->get('user_session');

            if ($userSession) {
                $configIP = Setting::configByKey(self::IP_RESTRICTION_KEY);
                $configDevice = Setting::configByKey(self::DEVICE_RESTRICTION_KEY);

                $session = $this->getActiveSession($user, $userSession);

                if ($session) {
                    // Checks if the session has expired based on the IP address
                    $isSessionExpiredByIP = $configIP === '2' && $this->isSessionExpiredByIP($session, $request);
                    // Checks if the session has expired based on the device
                    $isSessionExpiredByDevice = $configDevice === '2' && $this->isSessionExpiredByDevice($session);
                    // Checks if the session has expired except the one within the active device
                    $isAnyRestrictionEnabled = $configIP === '1' || $configDevice === '1';

                    if ($isSessionExpiredByIP || $isSessionExpiredByDevice || $isAnyRestrictionEnabled) {
                        return $this->killSessionAndRedirect($session);
                    }
                }
            }
        }

        return $next($request);
    }

    private function getActiveSession(User $user, string $userSession): ?UserSession
    {
        return $user->sessions()
            ->where([
                ['is_active', true],
                ['token', $userSession],
            ])
            ->whereNotNull('expired_date')
            ->first();
    }

    /**
     * Checks if a user session is expired based on the IP address
     *
     * @param UserSession session
     * @param Request request
     *
     * @return bool
     */
    private function isSessionExpiredByIP(UserSession $session, Request $request): bool
    {
        $ip = $request->getClientIp() ?? $request->ip();

        return $session->ip_address === $ip && !is_null($session->expired_date);
    }

    /**
     * Checks if a user session is expired based on the device information
     *
     * @param UserSession session
     *
     * @return bool
     */
    private function isSessionExpiredByDevice(UserSession $session): bool
    {
        $agent = new Agent();
        // Get the device details from the request
        $agentDevice = $agent->device() ? $agent->device() : 'Unknown';
        $requestDevice = $this->formatDeviceInfo($agentDevice, $agent->deviceType(), $agent->platform());
        // Get the user's most recent session
        $sessionDevice = $this->formatDeviceInfo(
            $session->device_name, $session->device_type, $session->device_platform
        );

        return $requestDevice === $sessionDevice && !is_null($session->expired_date);
    }

    private function killSessionAndRedirect(UserSession $session): Response
    {
        // Mark session as inactive and kill it
        $session->update(['is_active' => false]);
        session()->regenerate(true);
        session()->flush();

        return redirect()
            ->route('login')
            ->with('login-error', __('Your session has been killed'));
    }

    private function formatDeviceInfo(string $deviceName, string $deviceType, string $devicePlatform): string
    {
        return Str::slug($deviceName . '-' . $deviceType . '-' . $devicePlatform);
    }
}
