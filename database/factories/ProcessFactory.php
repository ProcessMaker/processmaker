<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\User;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a process
 */
$factory->define(Process::class, function (Faker $faker) {

    return [
        'PRO_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_NAME' => $faker->sentence(3),
        'PRO_DESCRIPTION' => $faker->paragraph(3),
        'PRO_CREATE_USER' => function () {
            return factory(User::class)->create();
        }
    ];
});
