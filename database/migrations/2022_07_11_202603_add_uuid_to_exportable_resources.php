<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidToExportableResources extends Migration
{

    const TABLES = [
        'users',
        'processes',
        'scripts',
        'screens',
        'environment_variables',
        // 'signals',
        'data_sources',
        'data_source_callbacks',
        'data_source_scripts',
        'data_source_webhooks',
        'vocabularies',
        'collections',
        'groups',
        'process_categories',
        'screen_categories',
        'script_categories',
        'data_source_categories',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
