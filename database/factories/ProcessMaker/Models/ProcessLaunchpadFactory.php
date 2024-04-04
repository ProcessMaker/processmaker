<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\User;

/**
 * Model factory for ProcessLaunchpad
 */
class ProcessLaunchpadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'process_id' => function () {
                return Process::factory()->create()->getKey();
            },
            'properties' => json_encode(["icon" => "fa-icon"]),
        ];
    }
}
