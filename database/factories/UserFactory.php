<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Model factory for a User
 */
$factory->define(\ProcessMaker\Model\User::class, function (Faker $faker) {

    /**
     * @todo Determine if we need more base columns populated
     */
    return [
        'uid' => Uuid::uuid4(),
        'username' => $faker->userName,
        'firstname' => $faker->firstName,
        'lastname' => $faker->lastName,
        'password' => Hash::make($faker->password),
        'time_zone' => $faker->timezone,
        'lang' => 'en'
    ];
});
