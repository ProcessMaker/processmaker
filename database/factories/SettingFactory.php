<?php

use Faker\Generator as Faker;

/**
 * Model factory for a settings.
 */
$factory->define(ProcessMaker\Models\Setting::class, function (Faker $faker) {
    return [
        'key' => $faker->word(),
        'config' => json_encode(
            ['test' => $faker->sentence(1)]
        )
    ];
});
