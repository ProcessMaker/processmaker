<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
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
        $response = (new ExportController)->manifest('screen', $screen->id);
        $manifest = $response->getContent();

        return [
            'unique_template_id' => '',
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->unique()->sentence(20),
            'user_id' => User::factory()->create()->getKey(),
            'editing_screen_uuid' => $screen->uuid,
            'screen_type' => 'FORM',
            'media_collection' => $this->faker->unique()->name(),
            'manifest' => $manifest,
            'screen_custom_css' => null,
            'is_public' => false,
            'is_default_template' => false,
            'is_system' => false,
            'asset_type' => null,
            'version' => '1.0.0',
            'screen_category_id' => function () {
                return ScreenCategory::factory()->create()->getKey();
            },
        ];
    }

    public function withCustomCss()
    {
        return $this->state(function (array $attributes) {
            $screen = Screen::where('uuid', $attributes['editing_screen_uuid'])->first();
            $screen->fill(['custom_css' => 'body { background-color: red; }'])->save();
            $response = (new ExportController)->manifest('screen', $screen->id);
            $manifest = $response->getContent();

            return [
                'manifest' => $manifest,
            ];
        });
    }
}
