<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;

/**
 * Model factory for a process
 */
$factory->define(Process::class, function (Faker $faker) {
    $files = glob(Process::getProcessTemplatesPath() . '/*.bpmn');
    $emptyProcess = $faker->randomElement($files);
    return [
        'name' => $faker->unique()->sentence(3),
        'bpmn' => file_get_contents($emptyProcess),
        'description' => $faker->unique()->name,
        'status' => 'ACTIVE',
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'process_category_id' => function () {
            return factory(ProcessCategory::class)->create()->getKey();
        },
        'warnings' => []
    ];
});
