<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Artifact;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for an Artifact
 *
 */
$factory->define(Artifact::class, function (Faker $faker) {

    return [
        'ART_UID' => str_replace('-', '', Uuid::uuid4()),
        'ART_NAME' => $faker->sentence(),
        'ART_TYPE' => $faker->randomElement([Artifact::TYPE_HORIZONTAL_LINE, Artifact::TYPE_VERTICAL_LINE, Artifact::TYPE_TEXT_ANNOTATION]),
        'process_id'        => function () {
            return factory(Process::class)->create()->id;
        }
    ];
});
