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
        Schema::table('user_configuration', function (Blueprint $table) {
            $table->json('ui_configuration')->nullable()->default(null)->change();
            $table->json('ui_filters')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_configuration', function (Blueprint $table) {
            $table->dropColumn('ui_filters');
        });
    }
};
