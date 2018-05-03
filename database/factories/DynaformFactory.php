<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;

/**
 * Model factory for a Dynaform.
 */
$factory->define(Dynaform::class, function (Faker $faker) {
    $pro = factory(Process::class)->create();
    return [
        'DYN_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID' => $pro->PRO_ID,
        'PRO_UID' => $pro->PRO_UID,
        'DYN_TITLE' => $faker->sentence(3),
        'DYN_DESCRIPTION' => $faker->sentence(5),
        'DYN_TYPE' => $faker->randomElement(Dynaform::TYPE)
    ];
});