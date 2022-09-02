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
        'processes',
        'process_categories',
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
        MigrationHelper::removeUuidsFromTables(self::TABLES);
    }
}
