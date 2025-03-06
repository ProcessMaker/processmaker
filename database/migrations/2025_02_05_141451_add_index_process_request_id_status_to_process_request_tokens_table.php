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
            $table->index(['process_request_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->dropIndex(['process_request_id', 'status']);
        });
    }
};
