<?php

use Faker\Generator as Faker;

$factory->define(ProcessMaker\Models\Permission::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'type' => 'ROUTE',
        'guard_name' => $faker->sentence(2),
        'description' => $faker->sentence(5),
    ];
});
