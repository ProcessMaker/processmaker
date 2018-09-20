<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

/**
 * Model factory for a ProcessRequestToken
 */
$factory->define(ProcessRequestToken::class, function (Faker $faker) {
    return [
        'element_type' => 'TASK',
        'element_uuid' => $faker->uuid,
        'element_name' => $faker->name,
        'status' => $faker->randomElement(['ACTIVE','FAILING','COMPLETED','CLOSED','EVENT_CATCH']),
        'process_uuid' => function () {
            return factory(Process::class)->create()->uuid;
        },
        'process_request_uuid' => function () {
            return factory(ProcessRequest::class)->create()->uuid;
        },
        'user_uuid' => function () {
            return factory(User::class)->create()->uuid;
        },
        'completed_at' => $faker->dateTime,
        'due_at' => $faker->dateTime,
        'initiated_at' => $faker->dateTime,
        'riskchanges_at' => $faker->dateTime,
    ];
});
