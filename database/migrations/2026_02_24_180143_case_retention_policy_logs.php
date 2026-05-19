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
        Schema::create('case_retention_policy_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('process_id');
            $table->json('case_ids')->nullable();
            $table->bigInteger('deleted_count')->default(0);

            $table->bigInteger('total_time_taken')->nullable(); // in seconds
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_retention_policy_logs');
    }
};
