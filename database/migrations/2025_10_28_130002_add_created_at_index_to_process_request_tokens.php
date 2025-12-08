<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Create descending index on created_at for optimized queries
        DB::statement('CREATE INDEX idx_process_request_tokens_created_at_desc ON process_request_tokens (created_at DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_process_request_tokens_created_at_desc');
        });
    }
};
