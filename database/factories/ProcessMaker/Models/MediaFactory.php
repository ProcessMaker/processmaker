<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\User;

/**
 * Model factory for Media
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'model_id' => 1,
            'model_type' => $this->faker->randomElement(['ProcessMaker\Models\ProcessRequest']),
            'collection_name' => 'default',
            'name' => $this->faker->randomElement(['name1', 'name2', 'name3']),
            'file_name' => $this->faker->randomElement(['image.png', 'video.mp4', 'audio.mp3', 'application.pdf', 'document.odt']),
            'mime_type' => $this->faker->randomElement(['application/pdf', 'application/odt', 'audio/mp3', 'video/mp4', 'image/jpg', 'image/png']),
            'disk' => 'public',
            'size' => 2054,
            'manipulations' => [],
            'custom_properties' => [],
            'responsive_images' => 'image',
            'order_column' => 1,
        ];
    }
}
