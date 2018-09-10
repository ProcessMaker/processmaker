<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Models\GroupMember;

/**
 * Model factory for a Group
 */
$factory->define(GroupMember::class, function (Faker $faker) {
    return [         
        'member_uuid' => function () {
            return factory(User::class)->create()->uuid;
        },
        'member_type' => 'user',
        'group_uuid' => function () {
            return factory(Group::class)->create()->uuid;
        }
    ];
});
