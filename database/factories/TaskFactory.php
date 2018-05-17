<?php

use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {

    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'title' => $faker->sentence(4),
        'description' => $faker->paragraph,
        'type' => $faker->randomElement(['GATEWAYTOGATEWAY', 'NORMAL'])
    ];
});
