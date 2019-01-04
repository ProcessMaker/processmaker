<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

/**
 * Model factory for a process request
 */
$factory->define(ProcessRequest::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(3),
        'data' => [],
        'status' => $faker->randomElement(['DRAFT', 'ACTIVE', 'COMPLETED']),
        'callable_id' => $faker->randomDigit,
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        },
        'process_collaboration_id' => function () {
            return factory(ProcessCollaboration::class)->create()->getKey();
        }
    ];
});
