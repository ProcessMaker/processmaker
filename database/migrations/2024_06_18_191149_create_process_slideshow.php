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
        Schema::create('process_slideshow', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('process_id')->unique();
            $table->unsignedInteger('user_id')->nullable();
            $table->enum('published_version', ['A', 'B', 'AB']);
            $table->unsignedInteger('process_version_a_id')->constrained('process_versions')->nullable();
            $table->unsignedInteger('process_version_b_id')->constrained('process_versions')->nullable();
            $table->boolean('is_enable')->default(false);
            $table->string('url')->nullable()->index();
            $table->timestamps();
            // Foreign keys
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_slideshow');
    }
};
