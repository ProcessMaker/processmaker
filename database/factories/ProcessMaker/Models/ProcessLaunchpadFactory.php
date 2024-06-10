<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;

class ProcessLaunchpadFactory extends Factory
{
    protected $model = ProcessLaunchpad::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'process_id' => function () {
                return Process::factory()->create()->getKey();
            },
            'user_id' => null,
            'properties' => json_encode([]),
        ];
    }
}
