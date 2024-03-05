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
            'menu_group' => SettingsMenus::EMAIL_MENU_GROUP,
            'menu_group_order' => SettingsMenus::EMAIL_MENU_ORDER,
            'ui' => [
                'icon' => SettingsMenus::EMAIL_MENU_ICON,
            ]
        ]);
        // Menu 2. Integrations SettingsMenus::INTEGRATIONS_GROUP_ID = 2
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::INTEGRATIONS_MENU_GROUP,
            'menu_group_order' => SettingsMenus::INTEGRATIONS_MENU_ORDER,
            'ui' => [
                'icon' => SettingsMenus::INTEGRATIONS_MENU_ICON,
            ]
        ]);
        // Menu 3. Log-in & Auth SettingsMenus::LOG_IN_AUTH_GROUP_ID = 3
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::LOG_IN_AUTH_MENU_GROUP,
            'menu_group_order' => SettingsMenus::LOG_IN_AUTH_MENU_ORDER,
            'ui' => [
                'icon' => SettingsMenus::LOG_IN_AUTH_MENU_ICON,
            ]
        ]);
        // Menu 4. Users Settings SettingsMenus::USER_SETTINGS_GROUP_ID = 4
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::USER_SETTINGS_MENU_GROUP,
            'menu_group_order' => SettingsMenus::USER_SETTINGS_MENU_ORDER,
            'ui' => [
                'icon' => SettingsMenus::USER_SETTINGS_MENU_ICON,
            ]
        ]);
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $menus = DB::table('settings_menus')->select('id', 'menu_group')->get()->toArray();

        Setting::chunk(100, function ($settings) use ($menus) {
            foreach ($settings as $setting) {
                // Define the value of 'menu_group' based on 'group'
                switch ($setting->group) {
                    case 'Actions By Email':
                    case 'Email Default Settings':
                        $key = SettingsMenus::EMAIL_GROUP_ID;
                        $setting->group_id = $menus[$key]->id;
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
                        $key = SettingsMenus::LOG_IN_AUTH_GROUP_ID;
                        $setting->group_id = $menus[$key]->id;
                        break;
                    case 'User Signals':
                    case 'Users': // Additional Properties
                        $key = SettingsMenus::USER_SETTINGS_GROUP_ID;
                        $setting->group_id = $menus[$key]->id;
                        break;
                    case 'IDP': // Intelligent Document Processing
                    case 'DocuSign':
                    case 'External Integrations': // Enterprise Integrations
                        $key = SettingsMenus::INTEGRATIONS_GROUP_ID;
                        $setting->group_id = $menus[$key]->id;
                        break;
                    default:
                        break;
                }
                $setting->save();
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
        //
    }
}
