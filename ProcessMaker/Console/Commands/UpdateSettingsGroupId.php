<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Log;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;

class UpdateSettingsGroupId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:update-settings-group-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the column group_id in settings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
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
                    Log::notice(
                        "Settings group {$setting->group} = {$setting->group_id} updated successfully"
                    );
                    return $this->info("Settings group {$setting->group} updated successfully");
                }
            }
        });
    }
}
