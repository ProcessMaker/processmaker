<?php

use ProcessMaker\Model\Group;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;
use ProcessMaker\Model\User;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(TaskUser::class, function (Faker $faker) {

    $taggable = [
        User::class,
        Group::class,
    ];

    $model = factory($faker->randomElement($taggable))->create();

    return [
        'task_id' => function () {
            return factory(Task::class)->create()->id;
        },
        'type' => 1,
        'user_id' => $model->id,
        'task_users_type' => $model::TYPE
    ];
});

$factory->defineAs(TaskUser::class, 'user', function (Faker $faker) use ($factory) {
    $follow = $factory->raw(TaskUser::class);
    $extras = [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'task_users_type' => User::TYPE
    ];
    return array_merge($follow, $extras);
});

$factory->defineAs(TaskUser::class, 'group', function (Faker $faker) use ($factory) {
    $follow = $factory->raw(TaskUser::class);
    $extras = [
        'user_id' => function () {
            return factory(Group::class)->create(['status' => Group::STATUS_ACTIVE])->id;
        },
        'task_users_type' => Group::TYPE
    ];
    return array_merge($follow, $extras);
});
