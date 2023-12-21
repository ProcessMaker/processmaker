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

        $session = $user->session ?? new Session();

        $session->user_id = $user->id;
        $session->user_agent = request()->userAgent();
        $session->ip_address = request()->getClientIp() ?? request()->ip();
        $session->device_name = $agent->device();
        $session->device_type = $agent->deviceType();
        $session->device_platform = $agent->platform();
        $session->device_browser = $agent->browser();
        $session->token = Str::uuid()->toString();
        $session->save();

        session(['user_session' => $session->token]);
    }
}
