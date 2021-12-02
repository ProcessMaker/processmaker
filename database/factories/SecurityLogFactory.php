<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\SecurityLog;

$factory->define(SecurityLog::class, function (Faker $faker) {
    return [
        'event' => $faker->word(),
        'ip' => $faker->localIpv4(),
        'meta' => '',
        'user_id' => null,
        'occurred_at' => '2021-12-01 09:41:32',
    ];
});
