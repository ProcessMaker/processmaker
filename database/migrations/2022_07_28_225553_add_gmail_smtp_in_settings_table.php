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
        $n = $index ? "_{$index}" : $index;
        $hidden = $config && $config === 0 ? false : true;
        Setting::firstOrCreate(['key' => "EMAIL_CONNECTOR_MAIL_AUTH_METHOD{$n}"], [
            'format' => 'choice',
            'config' => '0',
            'name' => 'SMTP Authentication Method',
            'helper' => '',
            'group' => $groupName,
            'hidden' => $hidden,
            'ui' => [ 
                'options' => EmailConfig::authentication_methods, 
                'authorizedBadge' => false,
                'order' => 800,
            ],
        ]);
        Setting::firstOrCreate(['key' => "EMAIL_CONNECTOR_GMAIL_KEY{$n}"], [
            'format' => 'text',
            'config' => config("services.gmail.key"),
            'name' => 'Gmail Client ID',
            'helper' => '',
            'group' => $groupName,
            'hidden' => $hidden,
            'ui' => [
                'order' => 1100,
            ],
        ]);

        Setting::firstOrCreate(['key' => "EMAIL_CONNECTOR_GMAIL_SECRET{$n}"], [
            'format' => 'text',
            'config' => config("services.gmail.secret"),
            'name' => 'Gmail Client Secret',
            'helper' => '',
            'group' => $groupName,
            'hidden' => $hidden,
            'ui' => [
                'sensitive' => true,
                'order' => 1200,
            ],
        ]);

        Setting::firstOrCreate(['key' => "EMAIL_CONNECTOR_GMAIL_REDIRECT_URI{$n}"], [
            'format' => 'text',
            'config' => app()->config['app']['url'] . '/gmail/redirect',
            'name' => 'Gmail Redirect URI',
            'helper' => '',
            'group' => $groupName,
            'hidden' => $hidden,
            'readonly'=> true,
            'ui' => [
                'order' => 1300,
            ],
        ]);

         // Add [Authorize Account] button
        Setting::firstOrCreate(['key' => "EMAIL_CONNECTOR_AUTHORIZE_ACCOUNT{$n}"], [
            'format' => 'button',
            'config' => false,
            'name' => 'Authorize Account',
            'group' => $groupName,
            'hidden' => $hidden,
            'ui' => '{"props":{"variant":"outline-secondary", "position": "top", "order":"200"},"handler":"authorizeAccount"}',
        ]);
        
        $driver = Setting::select('config')->where('group', $groupName)->where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_DRIVER%')->firstorFail();
        $selectedDriver = $driver ? EmailConfig::drivers[$driver->config] : null;
        if ($selectedDriver === 'smtp') {
            EmailConfig::filterAuthMethods('0', $groupName, $n);
        }
    }
}
