<?php

namespace ProcessMaker\Events;

use jdavidbakr\MailTracker\Events\EmailSentEvent;

/**
 * Class EmailSent
 *
 * This event handler is triggered when a ProcessMaker email is sent.
 * It extracts the necessary data from the email headers and triggers the appropriate message event.
 */
class EmailSent extends BaseEmailEvent
{
    /**
     * Handle the email sent event
     *
     * @param EmailSentEvent $event The email sent event from MailTracker
     * @return void
     */
    public function handle(EmailSentEvent $event)
    {
        $this->processEmailEvent($event);
    }

    /**
     * @inheritdoc
     */
    protected function getEventType(): string
    {
        return 'Sent';
    }

    /**
     * @inheritdoc
     */
    protected function getMessageEventIdHeader(): string
    {
        return 'X-ProcessMaker-Sent-Message-Event-ID';
    }
}
