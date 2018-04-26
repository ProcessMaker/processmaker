<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\InputDocument;

/**
 * Model factory for a Input Document.
 */
$factory->define(InputDocument::class, function (Faker $faker) {
    $pro = factory(\ProcessMaker\Model\Process::class)->create();
    return [
        'INP_DOC_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID' => $pro->PRO_ID,
        'PRO_UID' => $pro->PRO_UID,
        'INP_DOC_TITLE' => $faker->sentence(3),
        'INP_DOC_DESCRIPTION' => $faker->sentence(5),
        'INP_DOC_FORM_NEEDED' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE)),
        'INP_DOC_ORIGINAL' => $faker->randomElement(InputDocument::DOC_ORIGINAL_TYPE),
        'INP_DOC_PUBLISHED' => $faker->randomElement(InputDocument::DOC_PUBLISHED_TYPE),
        'INP_DOC_TAGS' => $faker->randomElement(InputDocument::DOC_TAGS_TYPE)
    ];
});
