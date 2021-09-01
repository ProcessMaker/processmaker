<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

/**
 * Model factory for a Group
 */
$factory->define(Group::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(3),
        'description' => $faker->sentence,
        'manager_id' => function () {
            $userId = null;
            if (hasPackage('package-advanced-user-manager')) {
                $userId =  factory(User::class)->create()->getKey();
            }
            return $userId;
        },
        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
    ];
});
