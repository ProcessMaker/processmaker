<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Gateway;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Gateway
 *
 */
$factory->define(Gateway::class, function (Faker $faker) {

    return [
        'GAT_UID' => str_replace('-', '', Uuid::uuid4()),
        'GAT_NAME' => $faker->sentence(3),
        'GAT_TYPE' => $faker->randomElement([Gateway::TYPE_EMPTY, Gateway::TYPE_EXCLUSIVE, Gateway::TYPE_INCLUSIVE, Gateway::TYPE_PARALLEL, Gateway::TYPE_COMPLEX]),
        'GAT_DIRECTION' => $faker->randomElement([Gateway::DIRECTION_CONVERGING, Gateway::DIRECTION_DIVERGING, Gateway::DIRECTION_UNSPECIFIED, Gateway::DIRECTION_MIXED]),
        'GAT_EVENT_GATEWAY_TYPE' => $faker->randomElement([Gateway::EVENT_GATEWAY_TYPE_NONE, Gateway::EVENT_GATEWAY_TYPE_PARALLEL, Gateway::EVENT_GATEWAY_TYPE_EXCLUSIVE]),
        'PRO_ID' => function () {
            return factory(Process::class)->create()->PRO_ID;
        },
    ];
});
