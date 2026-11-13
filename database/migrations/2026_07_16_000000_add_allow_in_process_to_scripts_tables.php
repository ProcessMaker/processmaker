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
        Schema::table('scripts', function (Blueprint $table) {
            $table->boolean('allow_in_process')->default(false)->after('timeout');
        });

        Schema::table('script_versions', function (Blueprint $table) {
            $table->boolean('allow_in_process')->default(false)->after('timeout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table) {
            $table->dropColumn('allow_in_process');
        });

        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('allow_in_process');
        });
    }
};
