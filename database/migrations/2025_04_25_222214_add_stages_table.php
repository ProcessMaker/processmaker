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
        Schema::create('stages', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID for the stage
            $table->unsignedInteger('process_id')->nullable(); // Foreign key for the process
            $table->integer('process_version_id')->nullable();
            $table->string('stage_name'); // Name of the stage
            $table->decimal('total_amount', 10, 2)->default(0); // Total amount accumulated for the stage
            $table->timestamps(); // Created at and updated at timestamps

            // Foreign key constraint (assuming you have a processes table)
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
