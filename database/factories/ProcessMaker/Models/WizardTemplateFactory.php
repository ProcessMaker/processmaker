<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\WizardTemplate;

class WizardTemplateFactory extends Factory
{
    protected $model = WizardTemplate::class;

    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'unique_template_id' => $this->faker->uuid,
            'process_template_id' => null,
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->unique()->name(),
            'media_collection' => $this->faker->unique()->name(),
            'template_details' => '{}',
            'helper_process_id' => Process::factory()->create()->id,
        ];
    }
}
