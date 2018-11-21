<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Notification;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Notification
 */
$factory->define(Notification::class, function (Faker $faker) {
    return [
        'type' => $faker->word(),
        'notifiable_type' => $faker->word(),
        'notifiable_id' => $faker->numberBetween(1),
        'data' => '[]',
    ];
});
