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

    /**
     * Build a process with a specific template name
     *
     * Usage:
     *  Process::factory()->withTemplate('SingleTask.bpmn')->create();
     *
     * @param string $templateName The name of the template. e.g. 'SingleTask.bpmn'
     *
     * @return self
     */
    public function withTemplate(string $templateName): self
    {
        return $this->state(
            [
                'bpmn' => Process::getProcessTemplate($templateName),
            ]
        );
    }
}
