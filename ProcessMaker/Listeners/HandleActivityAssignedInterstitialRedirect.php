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
        if (empty($request)) {
            return;
        }

        $allowInterstitial = $event->getProcessRequestToken()->getInterstitial()['allow_interstitial'];
        if ($allowInterstitial) {
            $payloadUrl = route('tasks.edit', ['task' => $event->getProcessRequestToken()->id]);
        } else {
            $payloadUrl = route('requests.show', [
                'request' => $event->getProcessRequestToken()
                    ->getAttribute('process_request_id')
            ]);
        }
        $this->setRedirectTo($request,
            'javascript:redirectToTask',
            [
                'payloadUrl' => $payloadUrl,
                'tokenId' => $event->getProcessRequestToken()->id,
                'allowInterstitial' => $event->getProcessRequestToken()->getInterstitial()['allow_interstitial'],
            ]
        );
    }
}
