<?php

use Faker\Generator as Faker;

/**
 * Model factory for a settings.
 */
$factory->define(ProcessMaker\Models\Setting::class, function (Faker $faker) {
    $format = $faker->randomElement([
        'text',
        'array',
        'object',
        'boolean',
    ]);
    
    switch ($format) {
        case 'text':
            $config = $faker->sentence();
            break;
        case 'array':
        case 'object':
            $config = json_encode(
                ['test' => $faker->sentence(1)]
            );
            break;
        case 'boolean':
            $config = $faker->randomElement([0, 1]);
            break;
    }
    
    return [
        'key' => $faker->word(),
        'config' => $config,
        'name' => $faker->title(),
        'helper' => $faker->sentence(),
        'group' => $faker->title(),
        'format' => $format,
        'hidden' => false,
        'readonly' => false,
    ];
});
