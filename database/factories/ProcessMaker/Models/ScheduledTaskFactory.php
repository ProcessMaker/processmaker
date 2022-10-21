<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;

class ScheduledTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $token = ProcessRequestToken::factory()->make([]);

        return [
            'process_id' => function () use ($token) {
                $token->save();

                return $token->process->getKey();
            },
            'process_request_id' => function () use ($token) {
                $token->save();

                return $token->processRequest->getKey();
            },
            'process_request_token_id' => function () use ($token) {
                $token->save();

                return $token->getKey();
            },
            'type' => $this->faker->randomElement(['INTERMEDIATE_TIMER_EVENT', 'TIMER_START_EVENT', 'BOUNDARY_TIMER_EVENT']),
            'last_execution' => date('Y-m-d H:t:s'),
            'configuration' => '{}',
        ];
    }
}
