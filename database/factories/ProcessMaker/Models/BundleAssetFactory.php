<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\Process;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\BundleAsset>
 */
class BundleAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bundle_id' => function () {
                return Bundle::factory()->create()->getKey();
            },
            'asset_type' => Process::class,
            'asset_id' => function () {
                return Process::factory()->create()->getKey();
            },
        ];
    }
}
