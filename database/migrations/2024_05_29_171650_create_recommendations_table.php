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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->string('name');
            $table->text('description');
            $table->integer('min_matches')->nullable();
            $table->integer('dismiss_for_secs')->nullable();
            $table->json('advanced_filter')->nullable();
            $table->json('actions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
