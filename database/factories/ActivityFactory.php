<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Activity;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for an Activity
 *
 */
$factory->define(Activity::class, function (Faker $faker) {
    return [
        'ACT_UID'       => str_replace('-', '', Uuid::uuid4()),
        'ACT_NAME'      => $faker->sentence(3),
        'ACT_TYPE'      => Activity::TYPE_TASK,
        'ACT_TASK_TYPE' => Activity::TASK_TYPE_EMPTY,
        'ACT_LOOP_TYPE' => Activity::LOOP_TYPE_NONE,
        'process_id'        => function () {
            return factory(Process::class)->create()->id;
        }
    ];
});
