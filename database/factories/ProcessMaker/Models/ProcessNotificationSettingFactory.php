<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessNotificationSetting;

/**
 * Model factory for a Process Notification Setting
 */

class ProcessNotificationSettingFactory extends Factory
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
                return Process::factory()->create()->getKey();
            },
            'element_id' => null,
            'notifiable_type' => $this->faker->randomElement([
                'requester',
                'participants',
            ]),
            'notification_type' => $this->faker->randomElement([
                'started',
                'canceled',
                'completed',
            ]),
        ];
    }
}
