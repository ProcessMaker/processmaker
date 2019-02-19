<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\ProcessWebhook;

/**
 * Model factory for a process category.
 */
$factory->define(ProcessWebhook::class, function (Faker $faker) {
    return [
        'node' => $faker->slug,
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        },
        'token' => $faker->md5,
    ];
});
