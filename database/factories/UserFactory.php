<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;

$onePassword = Hash::make('oneOnlyPassword');

/**
 * Model factory for a User
 */
$factory->define(User::class, function (Faker $faker) use($onePassword) {

    return [
        'username' => $faker->unique()->userName,
        'email' => $faker->unique()->email,
        'password' => $onePassword,

        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),

        'firstname' => $faker->firstName,
        'lastname' => $faker->lastName,
        'address' => $faker->streetAddress,
        'city' => $faker->city,
        'state' => $faker->stateAbbr,
        'postal' => $faker->postcode,
        'country' => 'US',

        'phone' => $faker->phoneNumber,
        'fax' => $faker->phoneNumber,
        'cell' => $faker->phoneNumber,

        'title' => $faker->jobTitle,
        'birthdate' => $faker->dateTimeThisCentury,
        'timezone' => $faker->timezone,
        'datetime_format' => $faker->randomElement(['Y-m-d H:i', 'm/d/Y', 'm/d/Y h:i A', 'm/d/Y H:i']),
        'language' => 'en',
        'loggedin_at' => null,

        'is_administrator' => false,

    ];
});
