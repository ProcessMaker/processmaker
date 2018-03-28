<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a process category.
 */
$factory->define(\ProcessMaker\Model\ProcessCategory::class,
                 function (Faker $faker) {
    return [
        'CATEGORY_UID'  => str_replace('-', '', Uuid::uuid4()),
        'CATEGORY_NAME' => $faker->name(),
    ];
});
