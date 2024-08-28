<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Events\ActivityAssigned;

class HandleActivityAssignedInterstitialRedirect extends HandleRedirectListener
{
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
                    ->getAttribute('process_request_id'),
            ]);
        }
        $this->setRedirectTo($request,
            'redirectToTask',
            [
                'payloadUrl' => $payloadUrl,
                'tokenId' => $event->getProcessRequestToken()->id,
                'nodeId' => $event->getProcessRequestToken()->element_id,
                'userId' => $event->getProcessRequestToken()->user_id,
                'allowInterstitial' => $event->getProcessRequestToken()->getInterstitial()['allow_interstitial'],
            ]
        );
    }
}
