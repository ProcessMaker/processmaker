<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Script;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

$factory->define(Script::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'title' => $faker->sentence,
        'description' => $faker->sentence,
        'language' => $faker->randomElement(['php', 'lua', 'nodejs', 'golang']),
        'code' => $faker->sentence($faker->randomDigitNotNull),
        'process_id' => function() {
            return factory(Process::class)->create()->id;
        }
    ];
});
