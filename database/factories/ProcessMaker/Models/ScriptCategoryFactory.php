<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\ScriptCategory;

/**
 * Model factory for a script category.
 */
class ScriptCategoryFactory extends Factory
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
