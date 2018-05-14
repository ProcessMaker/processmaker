<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Trigger;
use ProcessMaker\Model\Process;

$factory->define(Trigger::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'description' => $faker->sentence,
        // Maybe put in random types? Are there other types of triggers?
        'type' => 'SCRIPT',
        'webbot' => $faker->paragraph,
        'param' => $faker->paragraph,
        'process_id' => function() {
            return factory(Process::class)->create()->id;
        }
    ];
});
