<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a Permission
 */
$factory->define(\ProcessMaker\Model\Permission::class, function (Faker $faker) {

    return [
        'PER_UID' => str_replace('-', '', Uuid::uuid4()),
        'PER_CODE' => $faker->word,
        'PER_STATUS' => \ProcessMaker\Model\Permission::STATUS_ACTIVE
    ];
});
