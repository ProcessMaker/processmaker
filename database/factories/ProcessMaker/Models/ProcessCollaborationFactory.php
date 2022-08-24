<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;

/**
 * Model factory for a process collaboration
 */

class ProcessCollaborationFactory extends Factory
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
        ];
    }
}
