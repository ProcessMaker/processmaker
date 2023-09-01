<?php

use Facades\ProcessMaker\ImportExport\MigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    const TABLES = [
        'process_requests',
        'process_request_tokens',
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
};
