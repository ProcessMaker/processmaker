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
        factory(ScreenCategory::class)->create([
            'name' => __('Uncategorized'),
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);
        factory(ScriptCategory::class)->create([
            'name' => __('Uncategorized'),
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);
        factory(ProcessCategory::class)->create([
            'name' => __('Uncategorized'),
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);
    }
}
