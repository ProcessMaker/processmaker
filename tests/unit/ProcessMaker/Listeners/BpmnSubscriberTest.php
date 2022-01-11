<?php

namespace ProcessMaker\Managers;

use Carbon\Carbon;
use DateTime;
use ProcessMaker\Listeners\BpmnSubscriber;
use Tests\TestCase;

class BpmnSubscriberTest extends TestCase
{
    public function testErrorHandler()
    {
        $subscriber = new BpmnSubscriber();
        $this->assertNull($subscriber->initErrorHandler(null, null));
    }
}
