<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Events\ActivityAssigned;

class HandleActivityAssignedInterstitialRedirect extends HandleRedirectListener
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
    public function handle(ActivityAssigned $event): void
    {
        $request = $event->getProcessRequestToken()->getInstance();
        $this->setRedirectTo($request, 'javascript:redirectToTask', $event->getProcessRequestToken()->id);
    }
}
