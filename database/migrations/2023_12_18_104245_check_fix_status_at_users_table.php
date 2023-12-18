<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';
        if (!$isMysql) {
            return;
        }
        // check if the column status is of type enum
        $column = DB::selectOne('SHOW COLUMNS FROM users WHERE Field = "status"');
        $isEnum = substr($column->Type, 0, 4) === 'enum';
        $isDefaultActive = $column->Default === 'ACTIVE';
        if (!$isEnum && $isDefaultActive) {
            return;
        }
        // change the column status to varchar
        DB::statement('ALTER TABLE users MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT "ACTIVE"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
