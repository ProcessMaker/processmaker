<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;

/**
 * Model factory for a process
 */
$factory->define(Process::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->sentence(3),
        'bpmn' => Process::getProcessTemplate('OnlyStartElement.bpmn'),
        'description' => $faker->unique()->name,
        'status' => 'ACTIVE',
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'process_category_id' => function () {
            return factory(ProcessCategory::class)->create()->getKey();
        },
        'warnings' => null,
    ];
});
