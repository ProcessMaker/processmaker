<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;

/**
 * Model factory for a process
 */
class ProcessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->sentence(3),
            'bpmn' => Process::getProcessTemplate('OnlyStartElement.bpmn'),
            'description' => $this->faker->unique()->name(),
            'status' => 'ACTIVE',
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'process_category_id' => function () {
                return ProcessCategory::factory()->create()->getKey();
            },
            'warnings' => null,
        ];
    }
}
