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
        Schema::create('media_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('media_id');
            $table->timestamps();
            // Indexes
            $table->index('user_id');
            $table->index('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_logs');
    }
};
