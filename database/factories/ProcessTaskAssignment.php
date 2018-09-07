<?php

use ProcessMaker\Models\Group;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(ProcessTaskAssignment::class, function (Faker $faker) {

    $taggable = [
        User::class,
        Group::class,
    ];

    $model = factory($faker->randomElement($taggable))->create();

    return [
        'process_task_uuid' => $faker->uuid,
        'assignment_uuid' => $model->uuid,
        'assignment_type' => $model instanceof User ? ProcessTaskAssignment::TYPE_USER
            : ProcessTaskAssignment::TYPE_GROUP
    ];
});

$factory->defineAs(ProcessTaskAssignment::class, 'user', function (Faker $faker) use ($factory) {
    $follow = $factory->raw(ProcessTaskAssignment::class);
    $extras = [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'assignment_type' => 'user'
    ];
    return array_merge($follow, $extras);
});

$factory->defineAs(ProcessTaskAssignment::class, 'group', function (Faker $faker) use ($factory) {
    $follow = $factory->raw(ProcessTaskAssignment::class);
    $extras = [
        'user_uuid' => function () {
            return factory(Group::class)->create()->id;
        },
        'assignment_type' => 'Group'
    ];
    return array_merge($follow, $extras);
});
