<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Lane;
use ProcessMaker\Model\Laneset;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Lane
 *
 */
$factory->define(Lane::class, function (Faker $faker) {

    return [
        'LAN_UID'  => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID'   => function () {
            return factory(Process::class)->create()->PRO_ID;
        },
        'LNS_UID'  => function () {
            return factory(Laneset::class)->create()->LNS_UID;
        },
        'LAN_NAME' => $faker->sentence(3),
    ];
});
