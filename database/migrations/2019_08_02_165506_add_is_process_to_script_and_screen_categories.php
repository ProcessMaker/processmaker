<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('script_categories', function (Blueprint $table) {
            $table->boolean('is_system')->after('status')->default(false);
        });

        Schema::table('screen_categories', function (Blueprint $table) {
            $table->boolean('is_system')->after('status')->default(false);
        });

        Schema::table('screens', function (Blueprint $table) {
            $table->string('key')->nullable()->default(null);
        });

        Schema::table('screen_versions', function (Blueprint $table) {
            $table->string('key')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
};
