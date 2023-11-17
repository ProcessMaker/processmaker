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
        Schema::table('screen_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('screen_versions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screen_versions', function (Blueprint $table) {
            if (Schema::hasColumn('screen_versions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
