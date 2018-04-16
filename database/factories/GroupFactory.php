<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\Group;

/**
 * Model factory for a Group
 */
$factory->define(Group::class, function (Faker $faker) {
    return [
        'GRP_UID' => str_replace('-', '', Uuid::uuid4()),
        'GRP_TITLE' => $faker->word,
        'GRP_STATUS' => $faker->randomElements([Group::STATUS_ACTIVE, Group::STATUS_INACTIVE]),
        'GRP_LDAP_DN' => $faker->randomElements(['']),
        'GRP_UX' => $faker->randomElements(['NORMAL'])
    ];
});
