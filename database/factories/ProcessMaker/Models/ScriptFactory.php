<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;

class ScriptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'key' => null,
            'title' => $this->faker->sentence(),
            'language' => 'php',
            'code' => $this->faker->sentence($this->faker->randomDigitNotNull()),
            'description' => $this->faker->sentence(),
            'run_as_user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'script_category_id' => function () {
                return ScriptCategory::factory()->create()->getKey();
            },
        ];
    }
}
