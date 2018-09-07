<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;

/**
 * Model factory for a process
 */
$factory->define(Process::class, function (Faker $faker) {
    $emptyProcess = $faker->file(Process::getProcessTemplatesPath());
    return [
        'name' => $faker->sentence(3),
        'bpmn' => file_get_contents($emptyProcess),
        'description' => $faker->paragraph(3),
        'status' => Process::STATUS_ACTIVE,
        'user_uuid' => function () {
            return factory(User::class)->create()->uuid;
        },
        'process_category_uuid' => function () {
            return factory(ProcessCategory::class)->create()->uuid;
        }
    ];
});
