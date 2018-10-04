<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Script;

$factory->define(Script::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'description' => $faker->sentence,
        'language' => $faker->randomElement(['php', 'lua']),
        'code' => $faker->sentence($faker->randomDigitNotNull)
    ];
});
