<?php

use Faker\Generator as Faker;
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
        'status' => $faker->randomElement(['ACTIVE','FAILING','COMPLETED','CLOSED','EVENT_CATCH']),
        'process_request_uuid' => function () {
            return factory(ProcessRequest::class)->create()->uuid;
        },
        'user_uuid' => function () {
            return factory(User::class)->create()->uuid;
        }
    ];
});
