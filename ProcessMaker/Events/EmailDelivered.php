<?php

namespace ProcessMaker\Events;

use jdavidbakr\MailTracker\Events\EmailDeliveredEvent;

/**
 * Class EmailDelivered
 *
 * This event handler is triggered when a ProcessMaker email is delivered to a recipient.
 * It extracts the necessary data from the email headers and triggers the appropriate message event.
 */
class EmailDelivered extends BaseEmailEvent
{
    /**
     * Handle the email delivered event
     *
     * @param EmailDeliveredEvent $event The email delivered event from MailTracker
     * @return void
     */
    public function handle(EmailDeliveredEvent $event)
    {
        $this->processEmailEvent($event);
    }

    /**
     * @inheritdoc
     */
    protected function getEventType(): string
    {
        return 'Delivered';
    }

    /**
     * @inheritdoc
     */
    protected function getMessageEventIdHeader(): string
    {
        return 'X-ProcessMaker-Delivered-Message-Event-ID';
    }
}
