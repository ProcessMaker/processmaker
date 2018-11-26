<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;

$factory->define(ProcessMaker\Models\ProcessPermission::class, function (Faker $faker) {
    return [
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        },
        'permission_id' => function () {
            return factory(Permission::class)->create()->getKey();
        },
        'assignable_type' => User::class,
        'assignable_id' => function () {
            return factory(User::class)->create()->getKey();
        }
    ];
});
