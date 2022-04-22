<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;

$factory->define(Script::class, function (Faker $faker) {
    return [
        'key' => null,
        'title' => $faker->sentence,
        'language' => 'php',
        'code' => $faker->sentence($faker->randomDigitNotNull),
        'description' => $faker->sentence,
        'run_as_user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'script_category_id' => function () {
            return factory(ScriptCategory::class)->create()->getKey();
        },
    ];
});
