<?php

use Illuminate\Support\Arr;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;

$onePassword = Hash::make('oneOnlyPassword');

/**
 * Model factory for a User
 */
$factory->define(User::class, function (Faker $faker) use ($onePassword) {

    $username = static function ($generated = '') use ($faker) {
        try {
            $username_generator = static function () use ($faker, &$generated) {
                $generated = \random_int(1, 100) > \random_int(1, 50)
                    ? $faker->unique()->userName.Arr::random(
                        ['\'','"','~','`','-','_','/','','$','|','@','!','%','=','*',')','(','+','#', '.']
                    ) : str_replace('.', '', $faker->unique()->userName);
            };

            $username_generator();

            while (User::where('username', '=', $generated)->exists()) {
                $username_generator();
            }

            return $generated;
        } catch (Exception $exception) {
            dump($exception);

            return $faker->unique()->userName;
        }
    };

    return [
        'username' => $username(),
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
        'force_change_password' => 0,

    ];
});
