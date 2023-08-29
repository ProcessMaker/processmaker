<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\User;

/**
 * Model factory for a ProcessVersion
 */
class ProcessVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $emptyProcess = Arr::random(glob(Process::getProcessTemplatesPath() . '/*.bpmn'));
        $process = Process::factory()->make();

        return [
            'bpmn' => file_get_contents($emptyProcess),
            'name' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(['ACTIVE', 'INACTIVE']),
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'description' => $this->faker->sentence(),
            'process_category_id' => function () {
                return ProcessCategory::factory()->create()->getKey();
            },
            'process_id' => function () use ($process) {
                $process->save();

                return $process->getKey();
            },
            'start_events' => function () use ($process) {
                $process->save();

                return json_encode($process->start_events);
            },
        ];
    }
}
