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
        if (!Schema::hasColumn('processes', 'asset_type')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->string('asset_type')->nullable();
            });
        }

        if (!Schema::hasColumn('scripts', 'asset_type')) {
            Schema::table('scripts', function (Blueprint $table) {
                $table->string('asset_type')->nullable();
            });
        }

        if (!Schema::hasColumn('screens', 'asset_type')) {
            Schema::table('screens', function (Blueprint $table) {
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
