<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Facades\ProcessMaker\ImportExport\MigrationHelper;

class AddUuidsToCore extends Migration
{

    const TABLES = [
        'environment_variables',
        'groups',
        'group_members',
        'processes',
        'processables',
        'process_categories',
        'process_notification_settings',
        'process_task_assignments',
        'screens',
        'screen_categories',
        'scripts',
        'script_categories',
        'script_executors',
        'users',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processables', function (Blueprint $table) {
            $table->increments('id')->first();
        });
        
        Schema::table('process_notification_settings', function (Blueprint $table) {
            $table->increments('id')->first();
        });

        MigrationHelper::addUuidsToTables(self::TABLES);
        MigrationHelper::populateUuids(self::TABLES);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processables', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('process_notification_settings', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        MigrationHelper::removeUuidsFromTables(self::TABLES);
    }
}
