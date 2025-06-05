<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddMenuFrameWhitelistDefault extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $groupId = SettingsMenus::getId(SettingsMenus::LOG_IN_AUTH_MENU_GROUP);
        $whiteListKey = [
            'key' => 'white_list_frame.default',
        ];
        $whiteListDefaultOption = [
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'group_id' => $groupId,
            'helper' => null,
            'config' => null,
            'name' => 'Default URL',
            'hidden' => false,
        ];

        Setting::firstOrCreate($whiteListKey, $whiteListDefaultOption);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        $whiteListKey = [
            'key' => 'white_list_frame.default',
        ];
        Setting::where($whiteListKey)->delete();
    }
}
