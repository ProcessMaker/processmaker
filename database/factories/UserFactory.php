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
        'USR_UID' => str_replace('-', '', Uuid::uuid4()),
        'USR_USERNAME' => $faker->userName,
        'USR_FIRSTNAME' => $faker->firstName,
        'USR_LASTNAME' => $faker->lastName,
        'USR_PASSWORD' => Hash::make($faker->password),
        'USR_DUE_DATE' => Carbon::now()->addYear(1),     // This date determines until when the user is able to login
        'USR_TIME_ZONE' => $faker->timezone
    ];
});
