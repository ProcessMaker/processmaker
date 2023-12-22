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
