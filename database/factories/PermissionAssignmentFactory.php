<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\User;

$factory->define(PermissionAssignment::class, function (Faker $faker) {

    $model = factory($faker->randomElement([
        User::class,
        Group::class,
    ]))->create();

    return [
        'permission_id' => function () {
            return factory(Permission::class)->create()->getKey();
        },
        'assignable_id' => $model->getKey(),
        'assignable_type' => get_class($model)
    ];
});

$factory->defineAs(PermissionAssignment::class, 'user', function () use ($factory) {
    $follow = $factory->raw(PermissionAssignment::class);
    $extras = [
        'id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'assignable_type' => User::class
    ];
    return array_merge($follow, $extras);
});

$factory->defineAs(PermissionAssignment::class, 'group', function () use ($factory) {
    $follow = $factory->raw(PermissionAssignment::class);
    $extras = [
        'id' => function () {
            return factory(Group::class)->create()->getKey();
        },
        'assignable_type' => Group::class
    ];
    return array_merge($follow, $extras);
});
