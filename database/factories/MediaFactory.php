<?php
use Faker\Generator as Faker;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\User;
/**
 * Model factory for Media
 */
$factory->define(Media::class, function (Faker $faker) {
    return [
        'model_id' => 1,
        'model_type' => $faker->randomElement(['ProcessMaker\Models\ProcessRequest']),
        'collection_name' => 'default',
        'name' => $faker->randomElement(['name1', 'name2', 'name3']),
        'file_name' => $faker->randomElement(['image.png', 'video.mp4', 'audio.mp3', 'application.pdf', 'document.odt']),
        'mime_type' => $faker->randomElement(['application/pdf', 'application/odt', 'audio/mp3', 'video/mp4', 'image/jpg', 'image/png']),
        'disk' => 'public',
        'size' => 2054,
        'manipulations' => [],
        'custom_properties' => [],
        'responsive_images' => 'image',
        'order_column' => 1
    ];
});