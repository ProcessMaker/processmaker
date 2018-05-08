<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a Permission
 */
$factory->define(\ProcessMaker\Model\Permission::class, function (Faker $faker) {

    return [
        'code' => $faker->word,
        'status' => \ProcessMaker\Model\Permission::STATUS_ACTIVE
    ];
});
