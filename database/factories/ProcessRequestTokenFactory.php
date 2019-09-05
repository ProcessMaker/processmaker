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
        'element_id' => $faker->randomDigit,
        'element_name' => $faker->name,
        'status' => $faker->randomElement(['ACTIVE','FAILING','COMPLETED','CLOSED','EVENT_CATCH']),
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        },
        'process_request_id' => function () {
            return factory(ProcessRequest::class)->create()->getKey();
        },
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'completed_at' => $faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
        'due_at' => $faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
        'initiated_at' => $faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
        'riskchanges_at' => $faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now')
    ];
});
