<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

/**
 * Model factory for a screen.
 */

class ScreenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(5),
            'screen_category_id' => function () {
                return ScreenCategory::factory()->create()->getKey();
            },
            'watchers' => [],
        ];
    }
}
