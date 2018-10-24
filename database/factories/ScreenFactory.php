<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Screen;

/**
 * Model factory for a screen.
 */
$factory->define(Screen::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(5)
    ];
});
