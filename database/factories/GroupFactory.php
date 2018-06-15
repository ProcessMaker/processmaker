<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Group;

/**
 * Model factory for a Group
 */
$factory->define(Group::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'status' => $faker->randomElement([Group::STATUS_ACTIVE, Group::STATUS_INACTIVE]),
        'ldap_dn' => '',
        'ux' => $faker->randomElement([Group::UX_NORMAL, Group::UX_SINGLE, Group::UX_SWITCHABLE, Group::UX_MOBILE])
    ];
});
