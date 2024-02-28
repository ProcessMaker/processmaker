<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateUpdateSettingsMenuOrder extends Upgrade
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
        // Rename some values in the column 'group'
        Setting::where('group', 'External Integrations')->update(['group' => 'Enterprise Integrations']);
        Setting::where('group', 'IDP')->update(['group' => 'Intelligent Document Processing']);
        Setting::where('group', 'Log-In Options')->update(['group' => 'Log-In and Password']);
        Setting::where('group', 'SSO')->update(['group' => 'Single Sign-On']);
        Setting::where('group', 'Users')->update(['group' => 'Additional Properties']);
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
                        $setting->menu_group = Setting::EMAIL_MENU_GROUP;
                        $setting->menu_group_icon = 'envelope-open-text';
                        $setting->menu_group_order = 1;
                        break;
                    case 'Log-In and Password':
                    case 'LDAP':
                    case 'Single Sign-On':
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
                        $setting->menu_group = Setting::LOG_IN_AUTH_MENU_GROUP;
                        $setting->menu_group_icon = 'sign-in-alt';
                        $setting->menu_group_order = 2;
                        break;
                    case 'User Signals':
                    case 'Additional Properties':
                        $setting->menu_group = Setting::USER_SETTINGS_MENU_GROUP;
                        $setting->menu_group_icon = 'users';
                        $setting->menu_group_order = 3;
                        break;
                    case 'Intelligent Document Processing':
                    case 'DocuSign':
                    case 'Enterprise Integrations':
                        $setting->menu_group = Setting::INTEGRATIONS_MENU_GROUP;
                        $setting->menu_group_icon = 'puzzle-piece';
                        $setting->menu_group_order = 4;
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
