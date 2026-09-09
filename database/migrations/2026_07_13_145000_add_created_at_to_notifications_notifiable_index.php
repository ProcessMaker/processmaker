<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE = 'notifications';

    private const INDEX_NAME = 'notifications_notifiable_type_notifiable_id_read_at_index';

    /**
     * Run the migrations.
     *
     * Prefer running during a low-workload window. Creating this index on a large
     * notifications table can acquire locks and briefly affect write throughput.
     */
    public function up(): void
    {
        // Replace the previous 3-column index with an extended one that includes
        // created_at DESC. Same index name is reused intentionally.
        if (Schema::hasIndex(self::TABLE, self::INDEX_NAME)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        if (!Schema::hasIndex(self::TABLE, self::INDEX_NAME)) {
            DB::statement(
                'CREATE INDEX ' . self::INDEX_NAME . ' ON ' . self::TABLE
                . ' (notifiable_type, notifiable_id, read_at, created_at DESC)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex(self::TABLE, self::INDEX_NAME)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }
    }
};
