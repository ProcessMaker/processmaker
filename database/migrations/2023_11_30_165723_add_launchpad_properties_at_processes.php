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
        Schema::table('processes', function (Blueprint $table) {
            $table->json('launchpad_properties')->nullable();
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->json('launchpad_properties')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn(['launchpad_properties']);
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn(['launchpad_properties']);
        });
    }
};
