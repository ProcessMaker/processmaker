<?php
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Task
 */

use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Task::class, function (Faker $faker) {

    return [
      'process_id' => factory(\ProcessMaker\Model\Process::class)->create()->id,
      'TAS_TITLE' => $faker->sentence(4),
      'TAS_DESCRIPTION' => $faker->paragraph,
      'TAS_TYPE' => $faker->randomElement(["GATEWAYTOGATEWAY","NORMAL"])

    ];
});
