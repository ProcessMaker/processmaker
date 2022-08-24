<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\ScreenCategory;

/**
 * Model factory for a screen category.
 */

class ScreenCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->sentence(),
            'status' => $this->faker->randomElement(
                ['ACTIVE', 'INACTIVE']
            ),
            'is_system' => false,
        ];
    }
}
