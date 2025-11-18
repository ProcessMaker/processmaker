<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('process_versions', function (Blueprint $table) {
            $table->index(['process_id', 'draft'], 'idx_pv_process_draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropIndex('idx_pv_process_draft');
        });
    }
};
