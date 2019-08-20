<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\ScriptCategory;

/**
 * Model factory for a script category.
 */
$factory->define(ScriptCategory::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->sentence(),
        'status' => 'ACTIVE',
        'is_system' => false
    ];
});