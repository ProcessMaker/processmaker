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
        Schema::create('cases_started', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('case_number')->unique();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('case_title', 255);
            $table->text('case_title_formatted');
            $table->string('case_status', 20);
            $table->json('processes');
            $table->json('requests');
            $table->json('request_tokens');
            $table->json('tasks');
            $table->json('participants');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->text('keywords');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['case_number']);
            $table->index(['user_id', 'case_status', 'created_at']);
            $table->index(['user_id', 'case_status', 'updated_at']);

            $table->fullText('case_title');
            $table->fullText('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases_started');
    }
};
