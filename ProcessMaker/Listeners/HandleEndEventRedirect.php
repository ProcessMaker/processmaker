<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ProcessCompleted;

class HandleEndEventRedirect extends HandleRedirectListener
{

    /**
     * Handle the event.
     */
    public function handle(ProcessCompleted $event): void
    {
        $request = $event->getProcessRequest();
        if (empty($request)) {
            return;
        }

        $userId = Auth::id();
        $requestId = $event->getProcessRequest()->id;
        $this->setRedirectTo($request, 'processCompletedRedirect', $event, $userId, $requestId);
    }
}
