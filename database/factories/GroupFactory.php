<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Group;

/**
 * Model factory for a Group
 */
$factory->define(Group::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(3),
    ];
});
