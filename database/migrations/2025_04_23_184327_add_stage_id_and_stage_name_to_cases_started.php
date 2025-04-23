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
            $table->string('stage_id')->nullable();
            $table->string('stage_name')->nullable();
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
        });
    }
};
