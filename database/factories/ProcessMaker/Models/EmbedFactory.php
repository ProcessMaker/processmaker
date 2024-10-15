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
    protected $model = Embed::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $process = Process::factory()->create();

        return [
            'model_id' => $process->id,
            'model_type' => Process::class,
            'mime_type' => $this->faker->randomElement(['text/url']),
            'custom_properties' => json_encode([]),
            'order_column' => 1,
        ];
    }
}
