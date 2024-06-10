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
        Schema::create('alternatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('process_id')->constrained('processes');
            $table->unsignedInteger('process_version_a_id')->constrained('process_versions')->nullable();
            $table->unsignedInteger('process_version_b_id')->constrained('process_versions')->nullable();
            $table->enum('published_version', ['A', 'B', 'AB']);
            $table->text('settings');
            $table->boolean('version_b_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alternatives');
    }
};
