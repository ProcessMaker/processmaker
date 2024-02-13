<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\User;

/**
 * Model factory for process templates
 */
class ScreenTemplatesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $screen = Screen::factory()->create();

        $response = (new ExportController)->manifest('process', $process->id);
        // TODO: Handle storing screen manifests
        // $manifest = json_decode($response->getContent(), true);

        return [
            'name' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->unique()->name(),
            'user_id' => User::factory()->create()->getKey(),
            'editing_screen_uuid' => null,
            // 'manifest' => json_encode($manifest),
            'manifest' => '{}',
            'is_system' => false,
            'asset_type' => null,
            'version' => '1.0.0',
            'screen_category_id' => function () {
                return ScreenCategory::factory()->create()->getKey();
            },
        ];
    }
}
