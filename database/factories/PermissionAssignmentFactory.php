<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\User;

$factory->define(PermissionAssignment::class, function (Faker $faker) {
    return [
        'permission_id' => function () {
            return factory(Permission::class)->create()->getKey();
        },
    ];
});