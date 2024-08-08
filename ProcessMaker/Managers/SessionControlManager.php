<?php

namespace ProcessMaker\Managers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Helpers that control if a user session is blocked by some session policy
 */
class SessionControlManager
{
    const IP_RESTRICTION_KEY = 'session-control.ip_restriction';
    const DEVICE_RESTRICTION_KEY = 'session-control.device_restriction';

    private ?User $user;
    private string $clientIp;

    public function __construct(?User $user, string $clientIp)
    {
        $this->user = $user;
        $this->clientIp = $clientIp;
    }

    /**
     *
     * If the session is blocked by some of the ProcessMaker policies, returns true, false otherwise
     *
     * @return bool
     */
    public function isSessionBlocked()
    {
        // Check if the user's session should be blocked based on IP restriction
        if ($this->blockedByIp()) {
            return true;
        }
        // Check if the user's session should be blocked based on device restriction
        if ($this->blockedByDevice()) {
            return true;
        }

        return false;
    }


    /**
     * Checks if a user's session is a duplicate based on their IP address.
     *
     * @return bool
     */
    public function blockSessionByIp(): bool
    {
        // Get the user's most recent session
        $session = $this->user->sessions->sortByDesc('created_at')->first();
        // Get the user's current IP address
        return $session->ip_address === $this->clientIp;
    }

    /**
     * Checks if the user's current session device matches the device used in the request
     *
     * @return bool
     */
    public function blockSessionByDevice(): bool
    {
        $agent = new Agent();
        // Get the device details from the request
        $agentDevice = $agent->device() ? $agent->device() : 'Unknown';
        $requestDevice = $this->formatDeviceInfo($agentDevice, $agent->deviceType(), $agent->platform());
        // Get the active user sessions
        $sessions = $this->user->sessions()
            ->where([
                ['is_active', true],
                ['expired_date', null],
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $openSessions = $sessions->reduce(function ($carry, $session) use ($requestDevice) {
            $sessionDevice = $this->formatDeviceInfo(
                $session->device_name, $session->device_type, $session->device_platform
            );

            if ($requestDevice !== $sessionDevice || $session->ip_address !== $this->clientIp) {
                return $carry + 1;
            } else {
                return $carry - 1;
            }
        }, 0);

        return $openSessions > 0;
    }

    public function blockedByIp(): bool
    {
        return Setting::configByKey(self::IP_RESTRICTION_KEY) === '1' && $this->blockSessionByIp();
    }

    public function blockedByDevice(): bool
    {
        return Setting::configByKey(self::DEVICE_RESTRICTION_KEY) === '1'
            && $this->blockSessionByDevice();
    }

    public function redirectToLogin(): Response
    {
        return redirect()
            ->route('login')
            ->with('login-error', __('You are already logged in'));
    }

    private function formatDeviceInfo(string $deviceName, string $deviceType, string $devicePlatform): string
    {
        return Str::slug($deviceName . '-' . $deviceType . '-' . $devicePlatform);
    }
}