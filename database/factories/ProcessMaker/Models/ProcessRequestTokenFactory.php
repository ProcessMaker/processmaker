<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

/**
 * Model factory for a ProcessRequestToken
 */

class ProcessRequestTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'element_type' => 'TASK',
            'element_id' => $this->faker->randomDigit,
            'element_name' => $this->faker->name,
            'status' => $this->faker->randomElement(['ACTIVE', 'FAILING', 'COMPLETED', 'CLOSED', 'EVENT_CATCH']),
            'process_id' => function () {
                return Process::factory()->create()->getKey();
            },
            'process_request_id' => function () {
                return ProcessRequest::factory()->create()->getKey();
            },
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'completed_at' => $this->faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
            'due_at' => $this->faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
            'initiated_at' => $this->faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
            'riskchanges_at' => $this->faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
        ];
    }
}
