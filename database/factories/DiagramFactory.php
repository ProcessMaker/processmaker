<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Diagram;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Diagram
 *
 */
$factory->define(Diagram::class, function (Faker $faker) {

    return [
        'DIA_UID'         => str_replace('-', '', Uuid::uuid4()),
        'DIA_NAME'        => $faker->sentence(3),
        'DIA_IS_CLOSABLE' => $faker->boolean(),
        'process_id'          => function () {
            return factory(Process::class)->create()->id;
        }
    ];
});
