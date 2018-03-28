<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a process
 */
$factory->define(\ProcessMaker\Model\Process::class, function (Faker $faker) {

    /**
     * @todo Determine if we need more base columns populated
     */
    return [
        'PRO_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_TITLE' => $faker->sentence(3),
        'PRO_DESCRIPTION' => $faker->paragraph(3)
    ];
});
