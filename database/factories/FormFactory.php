<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Form;

/**
 * Model factory for a Form.
 */
$factory->define(Form::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(5)
    ];
});
