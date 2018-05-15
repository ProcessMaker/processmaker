<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Group;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Group
 */
$factory->define(Group::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'title' => $faker->sentence(3),
        'status' => $faker->randomElement([Group::STATUS_ACTIVE, Group::STATUS_INACTIVE]),
        'ldap_dn' => '',
        'ux' => $faker->randomElement([Group::UX_NORMAL, Group::UX_SINGLE, Group::UX_SWITCHABLE, Group::UX_MOBILE])
    ];
});
