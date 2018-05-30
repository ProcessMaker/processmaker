<?php

namespace Tests\Unit;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Model\TaskNotification;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Tests create email server
     */
    public function testCreateEmailServer(): void
    {
        $faker = Faker::create();
        $type = $faker->randomElement([TaskNotification::TYPE_AFTER_ROUTING, TaskNotification::TYPE_RECEIVE]);
        $messageType = $faker->randomElement([TaskNotification::MESSAGE_TEXT, TaskNotification::MESSAGE_TEMPLATE]);

        $server = factory(TaskNotification::class)->create([
            'type' => $type,
            'message_type' => $messageType

        ]);

        $this->assertGreaterThan(0, $server->id);
        $this->assertEquals($type, $server->type);
        $this->assertEquals($messageType, $server->message_type);

    }

}