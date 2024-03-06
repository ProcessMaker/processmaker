<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('process_request_tokens', 'is_priority')) {
            Schema::table('process_request_tokens', function (Blueprint $table) {
                $table->boolean('is_priority')->default(false);
                $table->index('is_priority');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->dropIndex(['is_priority']);
            $table->dropColumn('is_priority');
        });
    }
};
