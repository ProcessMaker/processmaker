<?php

use ProcessMaker\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;

class AddOffice365SmtpInSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Office 365 as an option to the authenication methods array
        Setting::where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_AUTH_METHOD%')->update(
        [
            "ui" => [
                'order' => 800,
                'options' => EmailConfig::authentication_methods
            ]
        ]);

        // Create Office 365 SMTP configurations
        $emailServers = Setting::select('group', 'config')->where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_DRIVER%')->get();

        foreach($emailServers as $server) {
            $this->createNewSmtpConfigs($server->group, $server->config);
        }
    }

    private function createNewSmtpConfigs($groupName, $config) 
    {
        $index = EmailConfig::getServerIndexByName($groupName);
        $driverIndex = EmailConfig::getValueOf(config("mail.driver"), EmailConfig::drivers);
        EmailConfig::createServerSettings($groupName, $driverIndex, $index);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }    
}
