<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;

/**
 * Model factory for a Process Task Assignment
 */
class ProcessTaskAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $model = $this->faker->randomElement([
            User::class,
            Group::class,
        ]);

        return [
            'process_id' => function () {
                return Process::factory()->create()->getKey();
            },
            'process_task_id' => $this->faker->randomDigit,
            'assignment_id' => function () use ($model) {
                return factory($model)->create()->getKey();
            },
            'assignment_type' => $model,
        ];
    }

    public function user()
    {
        return $this->state(function () {
            $follow = $factory->raw(ProcessTaskAssignment::class);
            $extras = [
                'assignment_id' => function () {
                    return User::factory()->create()->getKey();
                },
                'assignment_type' => User::class,
            ];

            return array_merge($follow, $extras);
        });
    }

    public function group()
    {
        return $this->state(function () {
            $follow = $factory->raw(ProcessTaskAssignment::class);
            $extras = [
                'assignment_id' => function () {
                    return Group::factory()->create()->getKey();
                },
                'assignment_type' => Group::class,
            ];

            return array_merge($follow, $extras);
        });
    }
}
