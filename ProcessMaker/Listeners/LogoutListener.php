<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class LogoutListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if (Cache::has($session = Session::getId())) {
            Cache::forget($session);
        }
    }
}
