<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;

$factory->define(Script::class, function (Faker $faker) {
    return [
        'key' => null,
        'title' => $faker->sentence,
        'language' => $faker->randomElement(['php', 'lua']),
        'code' => $faker->sentence($faker->randomDigitNotNull),
        'description' => $faker->sentence,
        'script_category_id' => function () {
            return factory(ScriptCategory::class)->create()->getKey();
        }
    ];
});
