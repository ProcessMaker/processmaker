<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

/**
 * Model factory for a screen.
 */
$factory->define(Screen::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(5),
        'screen_category_id' => function () {
            return factory(ScreenCategory::class)->create()->getKey();
        }
    ];
});
