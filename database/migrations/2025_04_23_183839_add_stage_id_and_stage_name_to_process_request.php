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
            // Those columns will store the last stage information related to stage_id, stage_name
            $table->integer('last_stage_id')->nullable();
            $table->string('last_stage_name')->nullable();
            // This column will be used to display the percentage of advancement of the request through the stages.
            $table->float('progress')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->dropColumn('last_stage_id');
            $table->dropColumn('last_stage_name');
            $table->dropColumn('progress');
        });
    }
};
