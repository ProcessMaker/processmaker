<?php

namespace ProcessMaker\Listeners;

use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Models\UserSession as Session;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use ProcessMaker\Models\Setting;

class UserSession
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $agent = new Agent();
        $user = $event->user;

        $configIP = Setting::configByKey('session-control.ip_restriction');
        $configDevice = Setting::configByKey('session-control.device_restriction');

        // Get the IP address and device information
        $ip = request()->getClientIp() ?? request()->ip();
        $agentDevice = $agent->device();
        $agentDeviceType = $agent->deviceType();
        $agentPlatform = $agent->platform();

        // if block session by IP or Device is enabled, then mark all active sessions as inactive
        if ($configDevice === '1') {
            $user->sessions()
                ->where('is_active', true)
                ->where([
                    ['device_name', $agentDevice],
                    ['device_type', $agentDeviceType],
                    ['device_platform', $agentPlatform],
                ])
                ->update(['is_active' => false]);
        }
        // if kill session by IP or Device is enabled, then kill all active sessions
        if ($configIP === '2') {
            $user->sessions()
                ->where('is_active', true)
                ->where('ip_address', $ip)
                ->update(['expired_date' => now()->toDateTimeString()]);
        }

        if ($configDevice === '2') {
            $user->sessions()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($agentDevice, $agentDeviceType, $agentPlatform) {
                    $query->where('device_name', '!=', $agentDevice)
                        ->orWhere('device_type', '!=', $agentDeviceType)
                        ->orWhere('device_platform', '!=', $agentPlatform);
                })
                ->update(['expired_date' => now()->toDateTimeString()]);
        }

        $session = new Session([
            'user_agent' => request()->userAgent(),
            'ip_address' => $ip,
            'device_name' => $agentDevice,
            'device_type' => $agentDeviceType,
            'device_platform' => $agentPlatform,
            'device_browser' => $agent->browser(),
            'token' => Str::uuid()->toString(),
            'is_active' => true,
        ]);

        $user->sessions()->save($session);

        session(['user_session' => $session->token]);
    }
}
