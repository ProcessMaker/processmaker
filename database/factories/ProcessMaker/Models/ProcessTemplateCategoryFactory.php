<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\ProcessTemplateCategory;

/**
 * Model factory for a process category.
 */
class ProcessTemplateCategoryFactory extends Factory
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
            'status' => 'ACTIVE',
            'is_system' => false,
        ];
    }
}
