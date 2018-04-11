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
    return [
        'TAS_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_UID' => function () {
            return factory(Process::class)->create()->PRO_UID;
        },
        'TAS_TITLE' => $faker->sentence(3),
        'TAS_DESCRIPTION' => $faker->paragraph(3)
    ];
});
