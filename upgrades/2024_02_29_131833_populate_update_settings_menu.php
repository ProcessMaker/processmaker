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

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        Setting::chunk(100, function ($settings) {
            foreach ($settings as $setting) {
                // Define the value of 'menu_group' based on 'group'
                switch ($setting->group) {
                    case 'Actions By Email':
                    case 'Email Default Settings':
                        $id = SettingsMenus::getId(SettingsMenus::EMAIL_MENU_GROUP);
                        break;
                    case 'Log-In Options': // Log-In and Password
                    case 'LDAP':
                    case 'SSO': // Single Sign-On
                    case 'SCIM':
                    case 'Session Control':
                    case 'SSO - Auth0':
                    case 'SSO - Atlassian':
                    case 'SSO - Facebook':
                    case 'SSO - GitHub':
                    case 'SSO - Google':
                    case 'SSO - Keycloak':
                    case 'SSO - Microsoft':
                    case 'SSO - SAML':
                        $id = SettingsMenus::getId(SettingsMenus::LOG_IN_AUTH_MENU_GROUP);
                        break;
                    case 'User Signals':
                    case 'Users': // Additional Properties
                        $id = SettingsMenus::getId(SettingsMenus::USER_SETTINGS_MENU_GROUP);
                        break;
                    case 'IDP': // Intelligent Document Processing
                    case 'DocuSign':
                    case 'External Integrations': // Enterprise Integrations
                        $id = SettingsMenus::getId(SettingsMenus::INTEGRATIONS_MENU_GROUP);
                        break;
                    default:
                        $id = null;
                        break;
                }
                if ($id !== null) {
                    $setting->group_id = $id;
                    $setting->save();
                }
            }
        });
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

    /**
     * Get the Id related to the specific menu_group
     */
    private function getId($menuName)
    {
        return DB::table('settings_menus')->where('menu_group', $menuName)->pluck('id')->first();
    }
}
