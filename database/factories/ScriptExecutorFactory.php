<?php
use Faker\Generator as Faker;
use ProcessMaker\Models\ScriptExecutor;

$factory->define(ScriptExecutor::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'description' => $faker->sentence,
        'config' => '',
        'language' => 'php'
    ];
});
