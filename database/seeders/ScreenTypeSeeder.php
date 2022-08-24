<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ScreenType;

class ScreenTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ScreenType::factory()->create([
            'name' => 'FORM',
            'is_interactive' => true,
        ]);
        ScreenType::factory()->create([
            'name' => 'DISPLAY',
            'is_interactive' => false,
        ]);
    }
}
