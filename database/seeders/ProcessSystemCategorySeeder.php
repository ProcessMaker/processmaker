<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ProcessCategory;

class ProcessSystemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ProcessCategory::factory()->create([
            'name' => 'System',
            'is_system' => true,
        ]);
    }
}
