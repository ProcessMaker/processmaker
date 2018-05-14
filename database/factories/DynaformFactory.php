<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Dynaform.
 */
$factory->define(Dynaform::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(5)
    ];
});