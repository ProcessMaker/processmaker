<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\ScreenType;

/**
 * Model factory for a screen category.
 */
$factory->define(ScreenType::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->sentence(),
    ];
});
