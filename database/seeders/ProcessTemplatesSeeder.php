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

        // We need to decide if we want to seed the script or use the imported one

        // $phpScriptExecutor = ScriptExecutor::where('title', 'PHP Executor')->firstOrFail();

        // Script::updateOrCreate(
        //     ['title' => 'Create User on PM'],
        //     [
        //         'title' => 'Create User on PM',
        //         'description' => 'Creates a Vendor on the System',
        //         'language' => 'php',
        //         'code' => file_get_contents(app_path('Templates/Fixtures/create-user-script.php')),
        //         'timeout' => 60,
        //         'run_as_user_id' => $admin->getKey(),
        //         'status' => 'ACTIVE',
        //         'script_category_id' => ScriptCategory::where('name', 'Uncategorized')->firstOrFail()->getKey(),
        //         'script_executor_id' => $phpScriptExecutor->getKey(),
        //     ]
        // );

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
