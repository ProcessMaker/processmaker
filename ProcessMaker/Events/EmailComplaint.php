<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use jdavidbakr\MailTracker\Events\ComplaintMessageEvent;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;

class EmailComplaint
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function handle(ComplaintMessageEvent $event)
    {
        $tracker = $event->sent_email;
        $emailIdentifier = $tracker->getHeader('X-ProcessMaker-Email-ID');
        $messageEventId = $tracker->getHeader('X-ProcessMaker-Sent-Message-Event-ID');
        $requestIdentifier = $tracker->getHeader('X-ProcessMaker-Request-ID');

        if ($requestIdentifier && $messageEventId) {
            $request = ProcessRequest::where('id', $requestIdentifier)->first();
            if ($request) {
                $data = [
                    'email_id' => $emailIdentifier,
                ];
                WorkflowManager::triggerMessageEvent($request, $messageEventId, $data);
            }
        }
    }
}
