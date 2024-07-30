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
        Schema::table('screens', function (Blueprint $table) {
            $table->mediumText('watchers')->nullable()->change();
        });

        Schema::table('screen_versions', function (Blueprint $table) {
            $table->mediumText('watchers')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // It is not possible to revert this change because of Error code: 1406
    }
};
