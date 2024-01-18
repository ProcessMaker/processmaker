<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Events\SettingsUpdated;
use ProcessMaker\Models\UserSession;

class SessionControlSettingsUpdated
{
    /**
     * Handle the event.
     */
    public function handle(SettingsUpdated $event): void
    {
        if (
            $event->getSetting()->key === 'session-control.ip_restriction' &&
            intval($event->getSetting()->config) === 1
        ) {
            UserSession::expiresDuplicatedSessionByIP();
        } elseif (
            $event->getSetting()->key === 'session-control.device_restriction' &&
            intval($event->getSetting()->config) === 1
        ) {
            UserSession::expiresDuplicatedSessionByDevice();
        }
    }
}
