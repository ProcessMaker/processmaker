<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;

/**
 * Model factory for a process
 */
$factory->define(ProcessRequest::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(3),
        'data' => '{}',
        'status' => $faker->randomElement(['ACTIVE', 'COMPLETED']),
        'user_uuid' => function () {
            return factory(User::class)->create()->uuid;
        },
        'process_uuid' => function () {
            return factory(Process::class)->create()->uuid;
        },
        //'process_collaboration_uuid' => function () {
        //    return factory(Process::class)->create()->uuid;
        //}
    ];
});
