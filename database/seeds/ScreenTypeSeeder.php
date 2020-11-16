<?php

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
        factory(ScreenType::class)->create([
            'name' => 'FORM',
            'is_interactive' => true,
        ]);
        factory(ScreenType::class)->create([
            'name' => 'DISPLAY',
            'is_interactive' => false,
        ]);
    }
}
