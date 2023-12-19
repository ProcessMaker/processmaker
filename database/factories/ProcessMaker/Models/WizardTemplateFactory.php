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
            'process_template_id' => null,
            'helper_process_id' => Process::factory()->create()->id,
        ];
    }
}
