<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use ProcessMaker\Models\Setting;

class ChangeButtonsUiInSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update buttons UI
        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_ADD_MAIL_SERVER%')->update([
            'name' => 'Mail Server', 
            'ui' => '{"props":{"variant":"primary", "position": "top", "order": "100", "icon": "fas fa-plus"},"handler":"addMailServer"}',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_REMOVE_MAIL_SERVER%')->update([
            'name' => 'Remove Server', 
            'ui' => '{"props":{"variant":"outline-danger", "position": "bottom", "order":"100", "icon":"fas fa-trash-alt"},"handler":"removeMailServer"}',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_SEND_TEST_EMAIL%')->update([
            'ui' => '{"props":{"variant":"outline-secondary", "position": "top", "order":"300"},"handler":"mailTest"}',
        ]);

        // Add Mail Driver Options
        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_MAIL_DRIVER%')->update([
            'ui' => '{"options": ["smtp", "sendmail", "mailgun", "postmark", "ses", "Gmail", "Office 365"]}',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert buttons UI
        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_ADD_MAIL_SERVER%')->update([
            'name' => '+ Mail Server',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_REMOVE_MAIL_SERVER%')->update([
            'name' => '- Remove Server', 
            'ui' => '{"props":{"variant":"outline-secondary"},"handler":"removeMailServer"}'
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_SEND_TEST_EMAIL%')->update([
            'ui' => '{"props":{"variant":"primary"},"handler":"mailTest"}'
        ]);

        // Revert Mail Driver Options
        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_MAIL_DRIVER%')->update([
            'ui' => '{"options": ["smtp", "sendmail", "mailgun", "postmark", "ses"]}',
        ]);
    }
}
