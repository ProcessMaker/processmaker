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
        Schema::table('script_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('script_versions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('script_versions', function (Blueprint $table) {
            if (Schema::hasColumn('script_versions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
