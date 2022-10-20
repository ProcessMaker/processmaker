<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\SecurityLog;

class SecurityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'event' => $this->faker->word(),
            'ip' => $this->faker->localIpv4(),
            'meta' => '',
            'user_id' => null,
            'occurred_at' => '2021-12-01 09:41:32',
        ];
    }
}
