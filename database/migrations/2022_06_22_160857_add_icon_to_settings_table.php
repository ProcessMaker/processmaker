<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use ProcessMaker\Models\Setting;

class AddIconToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('format');
            $table->string('position')->nullable()->after('format');
        });
        
        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_ADD_MAIL_SERVER%')->update([
            'name' => 'Mail Server', 
            'icon' => '<i class="fas fa-plus"></i>',
            'position' => 'top',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_REMOVE_MAIL_SERVER%')->update([
            'name' => 'Remove Server', 
            'icon' => '<i class="fas fa-trash-alt"></i>', 
            'position' => 'bottom',
            'ui' => '{"props":{"variant":"outline-danger"},"handler":"removeMailServer"}',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_SEND_TEST_EMAIL%')->update([
            'ui' => '{"props":{"variant":"outline-secondary"},"handler":"mailTest"}',
            'position' => 'top',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('icon');
            $table->dropColumn('position');

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
        });
    }
}
