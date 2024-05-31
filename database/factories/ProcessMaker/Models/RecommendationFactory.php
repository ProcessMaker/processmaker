<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\Recommendation>
 */
class RecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::orderedUuid(),
            'status' => 'ACTIVE',
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'min_matches' => 3,
            'dismiss_for_secs' => 60 * 60 * 24 * 7,
            'advanced_filter' => [],
            'actions' => [],
        ];
    }
}
