<?php

use Faker\Generator as Faker;
/**
 * Model factory for a settings.
 */
$factory->define(ProcessMaker\Models\Setting::class, function (Faker $faker) {
    return [
        'key' => $faker->sentence(1),
        'config' => json_encode([])
    ];
});
