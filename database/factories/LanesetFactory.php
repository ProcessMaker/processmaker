<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Laneset;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Laneset
 *
 */
$factory->define(Laneset::class, function (Faker $faker) {

    return [
        'LNS_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID' => function () {
            return factory(Process::class)->create()->PRO_ID;
        },
        'LNS_NAME' => $faker->sentence(3),
    ];
});
