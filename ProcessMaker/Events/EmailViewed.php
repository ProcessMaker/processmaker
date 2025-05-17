<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use jdavidbakr\MailTracker\Events\ViewEmailEvent;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;

class EmailViewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function handle(ViewEmailEvent $event)
    {
        $tracker = $event->sent_email;
        $emailIdentifier = $tracker->getHeader('X-ProcessMaker-Email-ID');
        $messageEventId = $tracker->getHeader('X-ProcessMaker-Viewed-Message-Event-ID');
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
