<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;

/**
 * Model factory for a Process Notification Setting
 */
$factory->define(ProcessNotificationSetting::class, function (Faker $faker) {
    return [
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        },
        'element_id' => null,
        'notifiable_type' => $faker->randomElement([
            'requester',
            'participants',
        ]),
        'notification_type' => $faker->randomElement([
            'started',
            'canceled',
            'completed',
        ])
    ];
});
