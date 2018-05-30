<?php

use ProcessMaker\Model\EmailServer;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskNotification;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(TaskNotification::class, function (Faker $faker) {

    return [
        'uid' => Uuid::uuid4(),
        'task_id' => function () {
            return factory(Task::class)->create()->id;
        },
        'email_server_id' => function () {
            return factory(EmailServer::class)->create()->id;
        },
        'type' => $faker->randomElement([TaskNotification::TYPE_RECEIVE, TaskNotification::TYPE_AFTER_ROUTING]),
        'message_type' => $faker->randomElement([TaskNotification::MESSAGE_TEXT, TaskNotification::MESSAGE_TEMPLATE]),
        'subject_message' => $faker->sentence(5),
        'message' => $faker->sentence(20),
        'last_email' => $faker->randomElement([true, false]),
        'email_from_format' => $faker->randomElement([true, false]),
    ];
});
