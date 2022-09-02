<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenVersion;

/**
 * Model factory for a ScreenVersion
 */
$factory->define(ScreenVersion::class, function (Faker $faker) {
    $screen = factory(Screen::class)->create();

    return [
        'screen_id' => $screen->getKey(),
        'screen_category_id' => function () {
            return factory(ScreenCategory::class)->create()->getKey();
        },
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(3),
        'type' => $faker->randomElement(['FORM', 'DYSPLAY', 'CONVERSATIONAL', 'EMAIL']),
        'config' => $screen->config,
        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
    ];
});
