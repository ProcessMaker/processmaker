<?php

use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {

    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'title' => $faker->sentence(4),
        'description' => $faker->paragraph,
        'type' => $faker->randomElement([Task::TYPE_NORMAL, Task::TYPE_ADHOC, Task::TYPE_SUB_PROCESS, Task::TYPE_HIDDEN, Task::TYPE_GATEWAY, Task::TYPE_WEB_ENTRY_EVENT, Task::TYPE_END_MESSAGE_EVENT, Task::TYPE_START_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT, Task::TYPE_SCRIPT_TASK, Task::TYPE_START_TIMER_EVENT, Task::TYPE_INTERMEDIATE_CATCH_TIMER_EVENT, Task::TYPE_END_EMAIL_EVENT, Task::TYPE_INTERMEDIATE_THROW_EMAIL_EVENT, Task::TYPE_SERVICE_TASK]),
        'assign_type' => $faker->randomElement([Task::ASSIGN_TYPE_BALANCED, Task::ASSIGN_TYPE_MANUAL, Task::ASSIGN_TYPE_EVALUATE, Task::ASSIGN_TYPE_REPORT_TO, Task::ASSIGN_TYPE_SELF_SERVICE, Task::ASSIGN_TYPE_STATIC_MI, Task::ASSIGN_TYPE_CANCEL_MI, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE_VALUE_BASED]),
        'routing_type' => $faker->randomElement([Task::ROUTE_TYPE_NORMAL, Task::ROUTE_TYPE_FAST, Task::ROUTE_TYPE_AUTOMATIC]),

        'timing_control_configuration' => [
            'duration' => $faker->randomDigitNotNull,
            'delay_type' => $faker->randomElement([Task::TIME_MINUTES, Task::TIME_HOURS, Task::TIME_DAYS]),
            'temporizer' => $faker->randomDigitNotNull,
            'type_day' => $faker->randomElement([Task::WORK_DAYS, Task::CALENDAR_DAYS]),
            'time_unit' => $faker->randomElement([Task::TIME_MINUTES, Task::TIME_HOURS, Task::TIME_DAYS, Task::TIME_WEEKS, Task::TIME_MONTHS]),
        ],
        'self_service_timeout_configuration' => [
            'self_service_timeout' => $faker->randomDigitNotNull,
            'self_service_time' => $faker->randomDigitNotNull,
            'self_service_time_unit' => $faker->randomElement([Task::TIME_MINUTES, Task::TIME_HOURS, Task::TIME_DAYS, Task::TIME_WEEKS, Task::TIME_MONTHS]),
            'self_service_execution' => $faker->randomElement([Task::EXECUTION_EVERY_TIME, Task::EXECUTION_ONCE]),
        ]

    ];
});
