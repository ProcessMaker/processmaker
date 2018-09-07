<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

/**
 * Model factory for a ProcessVersion
 */
$factory->define(ProcessVersion::class, function (Faker $faker) {
    $emptyProcess = $faker->file(Process::getProcessTemplatesPath());
    return [
        'bpmn' => file_get_contents($emptyProcess),
        'name' => $faker->sentence(3),
        'status' => ProcessVersion::STATUS_ACTIVE,
        'process_category_uuid' => function () {
            return factory(ProcessCategory::class)->create()->uuid;
        },
        'process_uuid' => function () {
                return factory(Process::class)->create()->uuid;
        }
    ];
});
