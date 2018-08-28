<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\ProcessCategory;

/**
 * Model factory for a process category.
 */
$factory->define(ProcessCategory::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'name' => $faker->name(),
        'status' => $faker->randomElement(
            [ProcessCategory::STATUS_ACTIVE, ProcessCategory::STATUS_INACTIVE]
        )
    ];
});
