<?php

namespace ProcessMaker\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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

        // if kill session by IP or Device is enabled, then kill all active sessions
        if ($configIP === '2' || $configDevice === '2') {
            $user->sessions()->where('is_active', true)->update(['expired_date' => now()]);
        }

        $session = new Session([
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->getClientIp() ?? request()->ip(),
            'device_name' => $agent->device(),
            'device_type' => $agent->deviceType(),
            'device_platform' => $agent->platform(),
            'device_browser' => $agent->browser(),
            'token' => Str::uuid()->toString(),
            'is_active' => true,
        ]);

        $user->sessions()->save($session);

        session(['user_session' => $session->token]);
    }
}
