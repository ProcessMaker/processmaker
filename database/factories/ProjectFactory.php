<?php
/**
 * Model factory for a project
 */
use Faker\Generator as Faker;

$factory->define(\ProcessMaker\Model\Project::class, function (Faker $faker) {

    /**
     * @todo Determine if we need more base columns populated
     */
    return [
        'PRJ_UID' => function () {
            $pro = factory(\ProcessMaker\Model\Process::class)->create();
            return $pro->PRO_UID;
        },
        'PRJ_NAME' => $faker->sentence(3),
        'PRJ_DESCRIPTION' => $faker->paragraph(3)
    ];
});
