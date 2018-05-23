<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;

/**
 * Model factory for a Input Document.
 */
$factory->define(InputDocument::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(5),
        'form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE)),
        'original' => $faker->randomElement(InputDocument::DOC_ORIGINAL_TYPE),
        'published' => $faker->randomElement(InputDocument::DOC_PUBLISHED_TYPE),
        'tags' => $faker->randomElement(InputDocument::DOC_TAGS_TYPE)
    ];
});
