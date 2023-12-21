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
        if (!Schema::hasColumn('process_versions', 'asset_type')) {
            Schema::table('process_versions', function (Blueprint $table) {
                $table->string('asset_type')->nullable();
            });
        }

        if (!Schema::hasColumn('script_versions', 'asset_type')) {
            Schema::table('script_versions', function (Blueprint $table) {
                $table->string('asset_type')->nullable();
            });
        }

        if (!Schema::hasColumn('screen_versions', 'asset_type')) {
            Schema::table('screen_versions', function (Blueprint $table) {
                $table->string('asset_type')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
