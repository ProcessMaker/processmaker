<?php
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Role
 */
use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Role::class, function (Faker $faker) {

    return [
        'ROL_UID' => str_replace('-', '', Uuid::uuid4()),
        'ROL_CODE' => $faker->word,
        'ROL_STATUS' => \ProcessMaker\Model\Role::STATUS_ACTIVE
    ];
});
