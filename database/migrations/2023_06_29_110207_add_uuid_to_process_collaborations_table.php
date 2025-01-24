<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('process_collaborations', function (Blueprint $table) {
            // add uuid column
            $table->uuid('uuid')->nullable()->after('id');
            $table->index('uuid', 'idx_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('process_collaborations', function (Blueprint $table) {
            // drop collaboration_uuid column
            $table->dropColumn('uuid');
        });
    }
};
