<?php

namespace Database\Factories\ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

class ProcessRequestTokenFactory extends Factory
{
    protected $model = ProcessRequestToken::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
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
            'due_at' => Carbon::now()->addDays(3),
            'initiated_at' => $this->faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
            'riskchanges_at' => $this->faker->dateTimeBetween($startDate = '-15 years', $endDate = 'now'),
        ];
    }

    public function overdue(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'ACTIVE',
                'due_at' => Carbon::yesterday(),
                'completed_at' => null,
            ];
        });
    }
}
