<?php

use Faker\Generator as Faker;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;

$factory->define(ProcessPermission::class, function (Faker $faker) {

    $model = factory($faker->randomElement([
        User::class,
        Group::class,
    ]))->create();

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

$factory->defineAs(ProcessPermission::class, 'user', function () use ($factory) {
    $follow = $factory->raw(ProcessPermission::class);
    $extras = [
        'id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'assignable_type' => User::class
    ];
    return array_merge($follow, $extras);
});

$factory->defineAs(ProcessPermission::class, 'group', function () use ($factory) {
    $follow = $factory->raw(ProcessPermission::class);
    $extras = [
        'id' => function () {
            return factory(Group::class)->create()->getKey();
        },
        'assignable_type' => Group::class
    ];
    return array_merge($follow, $extras);
});
