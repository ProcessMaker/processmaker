<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Model factory for a settings.
 */
class SettingsMenusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $menus = $this->faker->randomElement([
            'Email',
            'Integrations',
            'Log-In & Auth',
            'User Settings',
        ]);
        return [
            'menu_group' => $menus,
            'menu_group_order' => 1,
            'ui' => json_encode(["icon" => "envelope-open-text"]),
        ];
    }
}
