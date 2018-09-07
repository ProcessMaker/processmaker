<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;

/**
 * Model factory for a User
 */
$factory->define(\ProcessMaker\Models\User::class, function (Faker $faker) {

    return [
        'name' => $faker->userName,
        'email' => $faker->email,
        'password' => Hash::make($faker->password),
    ];
});
