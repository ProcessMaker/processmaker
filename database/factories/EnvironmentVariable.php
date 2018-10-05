<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\EnvironmentVariable;

$factory->define(EnvironmentVariable::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->sentence,
        'value' => $faker->sentence
    ];
});
