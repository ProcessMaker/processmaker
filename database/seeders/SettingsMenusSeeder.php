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
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::EMAIL_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::EMAIL_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::EMAIL_MENU_ICON]),
        ]);
        // Menu 2. Integrations SettingsMenus::INTEGRATIONS_GROUP_ID = 2
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::INTEGRATIONS_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::INTEGRATIONS_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::INTEGRATIONS_MENU_ICON]),
        ]);
        // Menu 3. Log-in & Auth SettingsMenus::LOG_IN_AUTH_GROUP_ID = 3
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::LOG_IN_AUTH_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::LOG_IN_AUTH_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::LOG_IN_AUTH_MENU_ICON]),
        ]);
        // Menu 4. Users Settings SettingsMenus::USER_SETTINGS_GROUP_ID = 4
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::USER_SETTINGS_MENU_GROUP
        ], [
            'menu_group_order' => SettingsMenus::USER_SETTINGS_MENU_ORDER,
            'ui' => json_encode(["icon" => SettingsMenus::USER_SETTINGS_MENU_ICON]),
        ]);
    }
}
