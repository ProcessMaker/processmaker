<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use ProcessMaker\Models\Setting;

class AddGmailSmtpInSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailServers = Setting::select('group', 'config')->where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_DRIVER%')->get();

        foreach($emailServers as $server) {
            $this->createNewSmtpConfigs($server->group, $server->config);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('key', 'LIKE', "EMAIL_CONNECTOR_GMAIL%")->delete();
        Setting::where('key', 'LIKE', "EMAIL_CONNECTOR_MAIL_AUTH_METHOD%")->delete();
        Setting::where('key', 'LIKE', "EMAIL_CONNECTOR_AUTHORIZE_ACCOUNT%")->delete();
    }

    private function createNewSmtpConfigs($groupName, $config) 
    {
        $index = EmailConfig::getServerIndexByName($groupName);
        $driverIndex = EmailConfig::getValueOf(config("mail.driver"), EmailConfig::drivers);
        EmailConfig::createServerSettings($groupName, $driverIndex, $index);
    }
}
