<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Events\ActivityCompleted;

class HandleActivityCompletedRedirect extends HandleRedirectListener
{

    /**
     * Handle the event.
     */
    public function handle(ActivityCompleted $event): void
    {
        $request = $event->getProcessRequestToken()->getInstance();
        if (empty($request)) {
            return;
        }

        $this->setRedirectTo($request, 'processUpdated', $event);
    }
}
