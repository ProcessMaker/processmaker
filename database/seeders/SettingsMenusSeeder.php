<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\SettingsMenus;

class SettingsMenusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Menu 1. Email SettingsMenus::EMAIL_GROUP_ID = 1
        // Menu 2. Integrations SettingsMenus::INTEGRATIONS_GROUP_ID = 2
        // Menu 3. Log-in & Auth SettingsMenus::LOG_IN_AUTH_GROUP_ID = 3
        // Menu 4. Users Settings SettingsMenus::USER_SETTINGS_GROUP_ID = 4
        SettingsMenus::populateSettingMenus();
    }
}
