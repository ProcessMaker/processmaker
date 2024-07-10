<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\RecommendationUser>
 */
class RecommendationUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'recommendation_id' => function () {
                return Recommendation::factory()->create()->getKey();
            },
        ];
    }
}
