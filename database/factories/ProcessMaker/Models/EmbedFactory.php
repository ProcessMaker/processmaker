<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Embed;
use ProcessMaker\Models\Process;

/**
 * Model factory for Embed
 */
class EmbedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'model_id' => function () {
                return Process::factory()->create()->getKey();
            },
            'model_type' => $this->faker->randomElement(['ProcessMaker\Models\Process']),
            'mime_type' => $this->faker->randomElement(['text/url']),
            'custom_properties' => [],
            'order_column' => 1,
        ];
    }
}
