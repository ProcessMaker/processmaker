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
        Schema::create('encrypted_data', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('field_name');
            $table->unsignedInteger('request_id');
            $table->string('iv');
            $table->text('data');
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('field_name');
            $table->index('request_id');
            $table->unique(['field_name', 'request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encrypted_data');
    }
};
