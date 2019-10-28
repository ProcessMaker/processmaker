<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScriptCategory;

class SystemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProcessCategory::class)->create([
            'name' => 'System',
            'is_system' => true,
            'status' => 'ACTIVE'
        ]);
        
        factory(ScreenCategory::class)->create([
            'name' => 'System',
            'is_system' => true,
            'status' => 'ACTIVE'
        ]);
        
        factory(ScriptCategory::class)->create([
            'name' => 'System',
            'is_system' => true,
            'status' => 'ACTIVE'
        ]);
    }
}
