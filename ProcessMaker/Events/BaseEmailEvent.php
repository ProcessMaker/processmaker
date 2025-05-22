<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;

/**
 * Class BaseEmailEvent
 *
 * Base class for all email-related events that are triggered by MailTracker.
 * Contains common functionality for header extraction, request processing, and error handling.
 */
abstract class BaseEmailEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Get the event type name for logging
     *
     * @return string
     */
    abstract protected function getEventType(): string;

    /**
     * Get the message event ID header name
     *
     * @return string
     */
    abstract protected function getMessageEventIdHeader(): string;

    /**
     * Process the email event
     *
     * @param object $event The email event from MailTracker
     * @return void
     */
    public function processEmailEvent($event)
    {
        try {
            $tracker = $event->sent_email;
            $emailIdentifier = $tracker->getHeader('X-ProcessMaker-Email-ID');
            $messageEventId = $tracker->getHeader($this->getMessageEventIdHeader());
            $requestIdentifier = $tracker->getHeader('X-ProcessMaker-Request-ID');

            // Log the email event for debugging
            Log::debug($this->getEventType() . ' email event', [
                'email_id' => $emailIdentifier,
                'message_event_id' => $messageEventId,
                'request_id' => $requestIdentifier,
            ]);

            if ($requestIdentifier && $messageEventId) {
                $request = ProcessRequest::where('id', $requestIdentifier)->first();
                if ($request) {
                    $data = [
                        'email_id' => $emailIdentifier,
                    ];
                    WorkflowManager::triggerMessageEvent($request, $messageEventId, $data);
                } else {
                    Log::warning($this->getEventType() . ' but process request not found', [
                        'request_id' => $requestIdentifier,
                    ]);
                }
            } else {
                Log::warning($this->getEventType() . ' but missing required headers', [
                    'email_id' => $emailIdentifier,
                    'message_event_id' => $messageEventId,
                    'request_id' => $requestIdentifier,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling ' . $this->getEventType() . ' event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
