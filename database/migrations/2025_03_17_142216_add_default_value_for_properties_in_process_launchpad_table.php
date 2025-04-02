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
        Schema::table('process_launchpad', function (Blueprint $table) {
            $table->json('properties')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_launchpad', function (Blueprint $table) {
            $table->json('properties')->nullable(false)->default(null)->change();
        });
    }
};
