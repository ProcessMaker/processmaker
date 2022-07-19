<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidToExportableResources extends Migration
{

    const TABLES = [
        'environment_variables',
        'groups',
        'group_members',
        'processes',
        'processables', // Needs model
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

        foreach (self::TABLES as $table) {
            if (!Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->uuid('uuid')->after('id')->unique()->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('uuid');
                });
            }
        }
    }
}
