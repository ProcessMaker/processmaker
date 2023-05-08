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
        ProcessCategory::updateOrCreate(
            ['name'  => 'Default Templates'],
            [
                'name' => 'Default Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        );
        // create process templates seed data
        ProcessTemplates::updateOrCreate(
            ['name' => 'New Hire Onboarding'],
            [
                'name' => 'New Hire Onboarding',
                'description' => 'Reduce time and effort in the processes in which new hires are integrated within the company',
                'process_id' => null,
                'user_id' => null,
                'process_category_id' => ProcessCategory::where('name', 'Default Templates')->firstOrFail()->getKey(),
                'manifest' => file_get_contents(app_path('Templates/Fixtures/process-employee-onboarding-template.json')),
                'svg' => file_get_contents(app_path('Templates/Fixtures/process-employee-onboarding-template.svg')),
                'is_system' => 0,
                'key' => 'default_templates',
            ]);
        ProcessTemplates::updateOrCreate(
            ['name' => 'Leave of Absence'],
            [
                'name' => 'Leave of Absence',
                'description' => 'Automate request for any absences, paid or unpaid from your employees and contractors',
                'process_id' => null,
                'user_id' => null,
                'process_category_id' => ProcessCategory::where('name', 'Default Templates')->firstOrFail()->getKey(),
                'manifest' => file_get_contents(app_path('Templates/Fixtures/process-leave-of-absence-template.json')),
                'svg' => file_get_contents(app_path('Templates/Fixtures/process-leave-of-absence-template.svg')),
                'is_system' => 0,
                'key' => 'default_templates',
            ]);
        ProcessTemplates::updateOrCreate(
            ['name' => 'Expense Approval'],
            [
                'name' => 'Expense Approval',
                'description' => 'Extract the information from any payment slip picture and submit an expense approval request.',
                'process_id' => null,
                'user_id' => null,
                'process_category_id' => ProcessCategory::where('name', 'Default Templates')->firstOrFail()->getKey(),
                'manifest' => file_get_contents(app_path('Templates/Fixtures/process-expense-approval.json')),
                'svg' => file_get_contents(app_path('Templates/Fixtures/process-expense-approval.svg')),
                'is_system' => 0,
                'key' => 'default_templates',
            ]);

        ProcessTemplates::updateOrCreate(
            ['name' => 'Vendor Onboarding'],
            [
                'name' => 'Vendor Onboarding',
                'description' => 'Collect all information needed to approve a company as your new vendor.',
                'process_id' => null,
                'user_id' => null,
                'process_category_id' => ProcessCategory::where('name', 'Default Templates')->firstOrFail()->getKey(),
                'manifest' => file_get_contents(app_path('Templates/Fixtures/process-vendor-onboarding-template.json')),
                'svg' => file_get_contents(app_path('Templates/Fixtures/process-vendor-onboarding.svg')),
                'is_system' => 0,
                'key' => 'default_templates',
            ]);
    }
}
