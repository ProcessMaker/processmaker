<?php

use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddDefaultScreensForTasks extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', ['--class' => 'ScreenSystemSeeder']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
}
