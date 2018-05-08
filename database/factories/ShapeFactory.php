<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Shape;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a shape
 */
$factory->define(Shape::class, function (Faker $faker) {

    return [
        'BOU_UID'            => str_replace('-', '', Uuid::uuid4()),
        'BOU_X'              => $faker->numberBetween(0, 1024),
        'BOU_Y'              => $faker->numberBetween(0, 800),
        'BOU_WIDTH'          => $faker->numberBetween(100, 200),
        'BOU_HEIGHT'         => $faker->numberBetween(60, 120),
        'BOU_REL_POSITION'   => 1,
        'BOU_SIZE_IDENTICAL' => 0,
    ];
});
