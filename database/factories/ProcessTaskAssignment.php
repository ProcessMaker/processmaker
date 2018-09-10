<?php

use ProcessMaker\Models\Group;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;

/**
 * Model factory for a Process Task Assignment
 */

use Faker\Generator as Faker;

$factory->define(ProcessTaskAssignment::class, function (Faker $faker) {

    $model = factory($faker->randomElement([
        User::class,
        Group::class,
    ]))->create();

    return [
        'process_task_uuid' => $faker->uuid,
        'assignment_uuid' => $model->uuid,
        'assignment_type' => $model instanceof User ? 'USER' : 'GROUP'
    ];
});

$factory->defineAs(ProcessTaskAssignment::class, 'user', function (Faker $faker) use ($factory) {
    $follow = $factory->raw(ProcessTaskAssignment::class);
    $extras = [
        'uuid' => function () {
            return factory(User::class)->create()->uuid;
        },
        'assignment_type' => 'USER'
    ];
    return array_merge($follow, $extras);
});

$factory->defineAs(ProcessTaskAssignment::class, 'group', function (Faker $faker) use ($factory) {
    $follow = $factory->raw(ProcessTaskAssignment::class);
    $extras = [
        'uuid' => function () {
            return factory(Group::class)->create()->uuid;
        },
        'assignment_type' => 'GROUP'
    ];
    return array_merge($follow, $extras);
});
