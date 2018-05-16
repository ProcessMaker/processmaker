<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;

/**
 * Model factory for a Output Document.
 */
$factory->define(OutputDocument::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(5),
        'filename' => $faker->sentence(5),
        'report_generator' => $faker->randomElement(OutputDocument::DOC_REPORT_GENERATOR_TYPE),
        'generate' => $faker->randomElement(OutputDocument::DOC_GENERATE_TYPE),
        'type' => $faker->randomElement(OutputDocument::DOC_TYPE),
    ];
});
