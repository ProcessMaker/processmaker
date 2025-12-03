<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddMenuTranslationsToUsersSettings extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $groupId = SettingsMenus::getId(SettingsMenus::USER_SETTINGS_MENU_GROUP);
        $translationsKey = [
            'key' => 'translations.enabled',
        ];
        $translationsOption = [
            'format' => 'boolean',
            'group' => 'Translations',
            'group_id' => $groupId,
            'helper' => 'Select whether the translations button is enabled',
            'config' => true,
            'name' => 'Translations button',
            'hidden' => false,
        ];

        Setting::firstOrCreate($translationsKey, $translationsOption);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        $translationsKey = [
            'key' => 'translations.enabled',
        ];
        Setting::where($translationsKey)->delete();
    }
}
