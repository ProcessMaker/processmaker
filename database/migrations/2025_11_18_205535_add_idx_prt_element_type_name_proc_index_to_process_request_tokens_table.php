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
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->index(['element_type', 'element_name', 'process_id', 'id'], 'idx_prt_element_type_name_proc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_prt_element_type_name_proc');
        });
    }
};
