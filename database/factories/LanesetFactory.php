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
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'LNS_NAME' => $faker->sentence(3),
    ];
});
