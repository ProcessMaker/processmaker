<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateUpdateSettingsMenu extends Upgrade
{
    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        // Menu 1. Email SettingsMenus::EMAIL_GROUP_ID = 1
        // Menu 2. Integrations SettingsMenus::INTEGRATIONS_GROUP_ID = 2
        // Menu 3. Log-in & Auth SettingsMenus::LOG_IN_AUTH_GROUP_ID = 3
        // Menu 4. Users Settings SettingsMenus::USER_SETTINGS_GROUP_ID = 4
        SettingsMenus::populateSettingMenus();
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        // Update the setting.group_id with the corresponding category created in settings_menus
        Setting::updateAllSettingsGroupId();
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // Truncate the table settings_menus
        DB::table('settings_menus')->truncate();
        // Set the group_id
        DB::table('settings')->update([
            'group_id' => null,
        ]);
    }
}
