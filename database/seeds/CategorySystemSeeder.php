<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScriptCategory;

class CategorySystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ScreenCategory::factory()->create([
            'name' => __('Uncategorized'),
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);
        ScriptCategory::factory()->create([
            'name' => __('Uncategorized'),
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);
        ProcessCategory::factory()->create([
            'name' => __('Uncategorized'),
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);
    }
}
