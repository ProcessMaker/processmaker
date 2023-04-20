<?php

use Database\Seeders\ProcessTemplatesSeeder;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\ProcessTemplates;

class UpdateLeaveOfAbsenceAndNewHireOnboardingTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => ProcessTemplatesSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ProcessTemplates::where('name', 'New hire onboarding')->where('key', 'default_templates')->delete();
        ProcessTemplates::where('name', 'Leave of Absence')->where('key', 'default_templates')->delete();
    }
}
