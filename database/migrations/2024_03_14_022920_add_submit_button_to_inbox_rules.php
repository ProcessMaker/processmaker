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
        Schema::table('inbox_rules', function (Blueprint $table) {
            $table->json('submit_button')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_rules', function (Blueprint $table) {
            $table->dropColumn('submit_button');
        });
    }
};
