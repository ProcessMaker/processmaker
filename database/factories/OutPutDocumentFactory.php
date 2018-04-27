<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\OutPutDocument;

/**
 * Model factory for a Output Document.
 */
$factory->define(OutPutDocument::class, function (Faker $faker) {
    $pro = factory(\ProcessMaker\Model\Process::class)->create();
    return [
        'OUT_DOC_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID' => $pro->PRO_ID,
        'PRO_UID' => $pro->PRO_UID,
        'OUT_DOC_TITLE' => $faker->sentence(3),
        'OUT_DOC_DESCRIPTION' => $faker->sentence(5),
        'OUT_DOC_FILENAME' => $faker->sentence(5),
        'OUT_DOC_REPORT_GENERATOR' => $faker->randomElement(OutPutDocument::DOC_REPORT_GENERATOR_TYPE),
        'OUT_DOC_GENERATE' => $faker->randomElement(OutPutDocument::DOC_GENERATE_TYPE),
        'OUT_DOC_TYPE' => $faker->randomElement(OutPutDocument::DOC_TYPE),
    ];
});
