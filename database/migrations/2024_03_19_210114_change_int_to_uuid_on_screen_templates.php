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
            //In Laravel 11, you must explicitly specify if a value is nullable.
            $table->uuid('editing_screen_uuid')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screen_templates', function (Blueprint $table) {
            $table->unsignedInteger('editing_screen_uuid')->change();
        });
    }
};
