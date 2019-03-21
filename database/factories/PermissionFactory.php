<?php

use Faker\Generator as Faker;

$factory->define(ProcessMaker\Models\Permission::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(2),
        'name' => $faker->sentence(2),
        'group' => $faker->word(),
    ];
});
