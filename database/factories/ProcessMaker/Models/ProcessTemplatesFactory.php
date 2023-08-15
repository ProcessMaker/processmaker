<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\User;

/**
 * Model factory for process templates
 */
class ProcessTemplatesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $process = Process::factory()->create();

        $response = (new ExportController)->manifest('process', $process->id);
        $manifest = json_decode($response->getContent(), true);

        return [
            'name' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->unique()->name(),
            'user_id' => User::factory()->create()->getKey(),
            'manifest' => json_encode($manifest),
            'svg' => $process->svg,
            'version' => '1.0.0',
            'process_id' => $process->id,
            'process_category_id' => function () {
                return ProcessCategory::factory()->create()->getKey();
            },
        ];
    }
}
