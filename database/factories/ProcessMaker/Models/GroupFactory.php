<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

/**
 * Model factory for a Group
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['ACTIVE', 'INACTIVE']),
        ];
    }
}
