<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Notification;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a Notification
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->word(),
            'notifiable_type' => $this->faker->word(),
            'notifiable_id' => $this->faker->numberBetween(1),
            'data' => '[]',
            'url' => '',
        ];
    }
}
