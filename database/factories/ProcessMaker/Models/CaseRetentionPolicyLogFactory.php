<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\Process;

/**
 * Model factory for a settings.
 */
class CaseRetentionPolicyLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'process_id' => function () {
                return Process::factory()->create()->id;
            },
            'case_ids' => CaseNumber::factory()->count($this->faker->numberBetween(1, 1000))->create()->pluck('id')->toArray(),
            'deleted_count' => $this->faker->numberBetween(1, 1000),
            'total_time_taken' => $this->faker->numberBetween(1, 1000000),
            'deleted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
