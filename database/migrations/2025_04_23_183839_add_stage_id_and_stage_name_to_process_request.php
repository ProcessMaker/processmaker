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
        Schema::table('process_requests', function (Blueprint $table) {
            // Add a 'stage_id' column to store the identifier of the last stage
            // This will allow tracking which stage for the request
            $table->string('stage_id')->nullable();
            // Add a 'stage_name' column to store the name of the last stage
            // This will facilitate the identification of the stage
            $table->string('stage_name')->nullable();
            // This column will be used to display the percentage of advancement of the request through the stages.
            $table->float('progress')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->dropColumn('stage_id');
            $table->dropColumn('stage_name');
            $table->dropColumn('progress');
        });
    }
};
