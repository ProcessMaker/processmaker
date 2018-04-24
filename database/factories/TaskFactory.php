<?php
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Task::class, function (Faker $faker) {

    return [

      'PRO_UID' => function () {
            return factory(\ProcessMaker\Model\Process::class)->create()->PRO_UID;
        },
      'TAS_UID' => str_replace('-', '', Uuid::uuid4()),
      'TAS_TITLE' => $faker->sentence(4),
      'TAS_DESCRIPTION' => $faker->paragraph,
      'TAS_TYPE' => $faker->randomElement(["GATEWAYTOGATEWAY","NORMAL"])

    ];
});
