<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a process category.
 */
$factory->define(\ProcessMaker\Model\ProcessCategory::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'name' => $faker->name(),
    ];
});
