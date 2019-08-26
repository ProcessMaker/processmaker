<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use ProcessMaker\Model;
use Faker\Generator as Faker;

$factory->define(DataStoreCategory::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
        'is_system' => $faker->boolean,
    ];
});
