<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Flow;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Flow
 *
 */
$factory->define(Flow::class, function (Faker $faker) {

    return [
        'FLO_UID' => str_replace('-', '', Uuid::uuid4()),
        'FLO_TYPE' => $faker->randomElement([Flow::TYPE_DEFAULT, Flow::TYPE_SEQUENCE, Flow::TYPE_MESSAGE]),
        'FLO_X1' => $faker->numberBetween(0, 1024),
        'FLO_Y1' => $faker->numberBetween(0, 800),
        'FLO_X2' => $faker->numberBetween(0, 1024),
        'FLO_Y2' => $faker->numberBetween(0, 800),
        'FLO_POSITION' => 1,
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
    ];
});
