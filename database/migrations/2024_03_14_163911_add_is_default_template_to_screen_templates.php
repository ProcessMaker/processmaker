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
        Schema::table('screen_templates', function (Blueprint $table) {
            $table->boolean('is_default_template')->default(false)->after('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screen_templates', function (Blueprint $table) {
            $table->dropColumn('is_default_template');
        });
    }
};
