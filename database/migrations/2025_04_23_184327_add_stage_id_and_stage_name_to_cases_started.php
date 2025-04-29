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
        Schema::table('cases_started', function (Blueprint $table) {
            // This column will store information about the stages: stage_id, stage_name
            $table->json('stages')->nullable();
            // This column will be used to display the percentage of advancement of the case through the stages.
            $table->float('progress')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases_started', function (Blueprint $table) {
            $table->dropColumn('stage_id');
            $table->dropColumn('stage_name');
            $table->dropColumn('progress');
        });
    }
};
