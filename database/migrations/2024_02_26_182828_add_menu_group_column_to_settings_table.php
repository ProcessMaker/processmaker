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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('menu_group')->nullable()->default('Undefined');
            $table->string('menu_group_icon')->nullable()->default('start');
            $table->integer('menu_group_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('menu_group');
            $table->dropColumn('menu_group_icon');
            $table->dropColumn('menu_group_order');
        });
    }
};
