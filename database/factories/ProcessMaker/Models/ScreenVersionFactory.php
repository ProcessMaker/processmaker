<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenVersion;

/**
 * Model factory for a ScreenVersion
 */
class ScreenVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $screen = Screen::factory()->create();

        return [
            'screen_id' => $screen->getKey(),
            'screen_category_id' => function () {
                return ScreenCategory::factory()->create()->getKey();
            },
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['FORM', 'DYSPLAY', 'CONVERSATIONAL', 'EMAIL']),
            'config' => $screen->config,
            'status' => $this->faker->randomElement(['ACTIVE', 'INACTIVE']),
        ];
    }
}
