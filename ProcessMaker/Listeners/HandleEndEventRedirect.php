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
        // Do not redirect to child request summary if there is a previous redirect
        if ($request->parent_request_id && self::$redirectionMethod !== '') {
            return;
        }

        $userId = Auth::id();
        $requestId = $request->id;
        $this->setRedirectTo($request, 'processCompletedRedirect', $event, $userId, $requestId);
    }
}
