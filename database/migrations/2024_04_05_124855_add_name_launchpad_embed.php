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
        // Add a column name in the process launchpad
        Schema::table('process_launchpad', function (Blueprint $table) {
            $table->string('name')->after('uuid');
        });
        // Add a column name in the embed
        Schema::table('embed', function (Blueprint $table) {
            $table->string('name')->after('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the column name in the process launchpad
        Schema::table('process_launchpad', function (Blueprint $table) {
            $table->dropColumn(['name']);
        });
        // Drop the column name in the embed
        Schema::table('embed', function (Blueprint $table) {
            $table->dropColumn(['name']);
        });
    }
};
