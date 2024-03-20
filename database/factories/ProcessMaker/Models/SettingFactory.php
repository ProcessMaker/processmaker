<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Model factory for a settings.
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $format = $this->faker->randomElement([
            'text',
            'array',
            'object',
            'boolean',
        ]);

        switch ($format) {
            case 'text':
                $config = $this->faker->sentence();
                break;
            case 'array':
            case 'object':
                $config = json_encode(
                    ['test' => $this->faker->sentence(1)]
                );
                break;
            case 'boolean':
                $config = $this->faker->randomElement([0, 1]);
                break;
        }

        return [
            'key' => $this->faker->unique()->word(),
            'config' => $config,
            'name' => $this->faker->title(),
            'helper' => $this->faker->sentence(),
            'group' => $this->faker->title(),
            'format' => $format,
            'hidden' => false,
            'readonly' => false,
        ];
    }
}
