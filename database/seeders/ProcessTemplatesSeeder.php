<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;

class ProcessTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //load user admin
        $admin = User::where('username', 'admin')->firstOrFail();
        $processCategory = ProcessCategory::where('name', 'Uncategorized')->firstOrFail();
        $manifest = file_get_contents(app_path('Templates/Fixtures/process-employee-onboarding-template.json'));
        $svg = file_get_contents(app_path('Templates/Fixtures/process-employee-onboarding-template.svg'));

        // create process templates seed data
        ProcessTemplates::updateOrCreate(
            ['name' => 'Employee Onboarding 2023'],
            [
                'name' => 'Employee Onboarding 2023',
                'description' => 'New version of the employee onboarding process from veggieDonuts',
                'process_id' => null,
                'user_id' => $admin->getKey(),
                'process_category_id' => $processCategory->getKey(),
                'manifest' => $manifest,
                'svg' => $svg,
                'is_system' => 0,
            ]);
    }
}
