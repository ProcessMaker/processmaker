<?php

namespace ProcessMaker\Events;

use jdavidbakr\MailTracker\Events\LinkClickedEvent;

/**
 * Class EmailLinkClicked
 *
 * This event handler is triggered when a link in a ProcessMaker email is clicked.
 * It extracts the necessary data from the email headers and triggers the appropriate message event.
 */
class EmailLinkClicked extends BaseEmailEvent
{
    /**
     * Handle the link clicked event
     *
     * @param LinkClickedEvent $event The link clicked event from MailTracker
     * @return void
     */
    public function handle(LinkClickedEvent $event)
    {
        $this->processEmailEvent($event);
    }

    /**
     * @inheritdoc
     */
    protected function getEventType(): string
    {
        return 'Link clicked';
    }

    /**
     * @inheritdoc
     */
    protected function getMessageEventIdHeader(): string
    {
        return 'X-ProcessMaker-Link-Clicked-Message-Event-ID';
    }
}
