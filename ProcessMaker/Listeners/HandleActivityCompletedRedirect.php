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
        $token = $event->getProcessRequestToken();
        $request = $token->getInstance();

        if (empty($request)) {
            return;
        }

        $this->setRedirectTo($request, 'processUpdated', [
            'tokenId' => $token->id,
            'requestStatus' => $request->status,
        ]);
    }
}
