<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;

/**
 * Model factory for a Task
 */
$factory->define(Task::class, function (Faker $faker) {

    /**
     * @todo Determine if we need more base columns populated
     */
    $process = factory(Process::class)->create();
    return [
        'TAS_UID' => str_replace('-', '', Uuid::uuid4()),
        //'PRO_ID' => $process->PRO_ID,
        'PRO_UID' => $process->PRO_UID,
        'TAS_TITLE' => $faker->sentence(3),
        'TAS_DESCRIPTION' => $faker->paragraph(3)
    ];
});
