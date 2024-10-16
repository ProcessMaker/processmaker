<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\Bundle>
 */
class BundleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'published' => true,
            'version' => 0,
        ];
    }
}
