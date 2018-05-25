<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Carbon\Carbon;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Task;
use \ProcessMaker\Model\User;

$factory->define(Delegation::class, function (Faker $faker) {

    return [
        'uid' => Uuid::uuid4(),
        'application_id' => factory(Application::class)->create()->id,
        'task_id' => factory(Task::class)->create()->id,
        'user_id' => factory(User::class)->create()->id,
        'thread_status' => $faker->randomElement([Delegation::THREAD_STATUS_OPEN, Delegation::THREAD_STATUS_CLOSED]),
        'last_index' => $faker->randomElement(['0', '1']),
        'delegate_date' => Carbon::now(),
        'started'=> $faker->randomElement(['0', '1']),
        'finished'=> $faker->randomElement(['0', '1']),
        'delayed'=> $faker->randomElement(['0', '1']),
    ];
});
