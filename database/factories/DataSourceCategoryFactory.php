<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use ProcessMaker\Models\DataSourceCategory;
use Faker\Generator as Faker;

$factory->define(DataSourceCategory::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
        'is_system' => $faker->boolean,
    ];
});
