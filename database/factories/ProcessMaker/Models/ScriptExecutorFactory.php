<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\ScriptExecutor;

class ScriptExecutorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->sentence,
            'config' => '',
            'language' => 'php',
        ];
    }
}
