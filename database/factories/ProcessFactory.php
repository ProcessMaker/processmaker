<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\User;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a process
 */
$factory->define(Process::class, function (Faker $faker) {

    return [
        'uid'  => Uuid::uuid4(),
        'name' => $faker->sentence(3),
        'description' => $faker->paragraph(3),
        'status' => 'ACTIVE',
        'type' => 'NORMAL',
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'process_category_id' => function () {
            return factory(ProcessCategory::class)->create()->id;
        }
    ];
});
