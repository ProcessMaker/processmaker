<?php

use Faker\Generator as Faker;
use ProcessMaker\Enums\ActiveType;
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
        'status' => ActiveType::getRandomValue(),
        'process_category_id' => function () {
            return factory(ProcessCategory::class)->create()->getKey();
        },
        'process_id' => function () {
            return factory(Process::class)->create()->getKey();
        }
    ];
});

foreach (ActiveType::getKeys() as $key) {
    $factory->state(ProcessVersion::class, $key, function ($faker) use ($key) {
        return [
            'status' => ActiveType::getValue($key),
        ];
    });
}
