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
            $table->index('stage_id');
        });
        Schema::table('process_requests', function (Blueprint $table) {
            $table->index('last_stage_id');
        });
        Schema::table('cases_participated', function (Blueprint $table) {
            $table->index('last_stage_id');
        });
        Schema::table('cases_started', function (Blueprint $table) {
            $table->index('last_stage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->dropIndex(['stage_id']);
        });
        Schema::table('process_requests', function (Blueprint $table) {
            $table->dropIndex(['last_stage_id']);
        });
        Schema::table('cases_participated', function (Blueprint $table) {
            $table->dropIndex(['last_stage_id']);
        });
        Schema::table('cases_started', function (Blueprint $table) {
            $table->dropIndex(['last_stage_id']);
        });
    }
};
