<?php

namespace ProcessMaker\Listeners;

use Carbon\Carbon;
use ProcessMaker\Events\SessionStarted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActiveUserListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SessionStarted  $event
     * @return void
     */
    public function handle(SessionStarted $event)
    {
        $event->user->timestamps = false;
        $event->user->active_at = Carbon::now();
        $event->user->save();
    }
}
