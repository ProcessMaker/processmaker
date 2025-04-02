<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddMenuFrameWhitelist extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $group_id = SettingsMenus::getId(SettingsMenus::LOG_IN_AUTH_MENU_GROUP);
        $whiteListKey = [
            'key' => 'white_list_frame',
        ];
        $whiteListOption = [
            'format' => 'button',
            'group' => 'IFrame Whitelist Config',
            'group_id' => $group_id,
            'helper' => null,
            'config' => false,
            'name' => 'Add URL',
            'hidden' => true,
            'ui' => [
                "props" => [
                    "variant" => "primary",
                    "position" => "top",
                    "order" => "100",
                    "icon" => "fas fa-plus"
                ],
                "handler" => "addWhiteListURL"
            ]
            ];

        Setting::firstOrCreate($whiteListKey, $whiteListOption);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
