<?php

namespace ProcessMaker\Events;

use jdavidbakr\MailTracker\Events\ViewEmailEvent;

/**
 * Class EmailViewed
 *
 * This event handler is triggered when a ProcessMaker email is viewed by a recipient.
 * It extracts the necessary data from the email headers and triggers the appropriate message event.
 */
class EmailViewed extends BaseEmailEvent
{
    /**
     * Handle the email viewed event
     *
     * @param ViewEmailEvent $event The view email event from MailTracker
     * @return void
     */
    public function handle(ViewEmailEvent $event)
    {
        $this->processEmailEvent($event);
    }

    /**
     * @inheritdoc
     */
    protected function getEventType(): string
    {
        return 'Viewed';
    }

    /**
     * @inheritdoc
     */
    protected function getMessageEventIdHeader(): string
    {
        return 'X-ProcessMaker-Viewed-Message-Event-ID';
    }
}
