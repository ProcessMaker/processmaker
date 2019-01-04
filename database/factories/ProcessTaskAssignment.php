<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;

/**
 * Model factory for a Process Task Assignment
 */
$factory->define(ProcessTaskAssignment::class, function (Faker $faker) {

    $model = $faker->randomElement([
        User::class,
        Group::class,
    ]);

    return [
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        },
        'process_task_id' => $faker->randomDigit,
        'assignment_id' => function () use ($model) {
            return factory($model)->create()->getKey();
        },
        'assignment_type' => $model
    ];
});

$factory->defineAs(ProcessTaskAssignment::class, 'user', function () use ($factory) {
    $follow = $factory->raw(ProcessTaskAssignment::class);
    $extras = [
        'assignment_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'assignment_type' => User::class
    ];
    return array_merge($follow, $extras);
});

$factory->defineAs(ProcessTaskAssignment::class, 'group', function () use ($factory) {
    $follow = $factory->raw(ProcessTaskAssignment::class);
    $extras = [
        'assignment_id' => function () {
            return factory(Group::class)->create()->getKey();
        },
        'assignment_type' => Group::class
    ];
    return array_merge($follow, $extras);
});
