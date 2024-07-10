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
        Schema::create('process_abe_request_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('process_request_id')->nullable();
            $table->unsignedInteger('process_request_token_id')->nullable();
            $table->unsignedInteger('completed_screen_id')->nullable();
            $table->json('data');
            $table->boolean('is_answered')->default(false);
            $table->dateTime('answered_at')->nullable();
            $table->timestamps();
            // Indexes
            $table->index('process_request_id');
            $table->index('process_request_token_id');
            // Foreing keys
            $table->foreign('process_request_token_id')->references('id')
                ->on('process_request_tokens')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_abe_request_tokens');
    }
};
