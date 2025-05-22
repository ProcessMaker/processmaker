<?php

namespace ProcessMaker\Events;

use jdavidbakr\MailTracker\Events\ComplaintMessageEvent;

/**
 * Class EmailComplaint
 *
 * This event handler is triggered when a ProcessMaker email receives a complaint.
 * It extracts the necessary data from the email headers and triggers the appropriate message event.
 */
class EmailComplaint extends BaseEmailEvent
{
    /**
     * Handle the email complaint event
     *
     * @param ComplaintMessageEvent $event The complaint message event from MailTracker
     * @return void
     */
    public function handle(ComplaintMessageEvent $event)
    {
        $this->processEmailEvent($event);
    }

    /**
     * @inheritdoc
     */
    protected function getEventType(): string
    {
        return 'Complaint';
    }

    /**
     * @inheritdoc
     */
    protected function getMessageEventIdHeader(): string
    {
        return 'X-ProcessMaker-Complaint-Message-Event-ID';
    }
}
