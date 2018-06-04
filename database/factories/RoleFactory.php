<?php
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Role
 */
use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Role::class, function (Faker $faker) {

    return [
        'code' => $faker->word,
        'name' => $faker->word,
        'description' => $faker->sentence,
        'status' => \ProcessMaker\Model\Role::STATUS_ACTIVE
    ];
});
