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
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->after('id')->default(0);
            $table->unsignedBigInteger('group_id')->after('parent_id')->nullable();
            $table->string('group_name')->after('group_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('parent_id');
            $table->dropColumn('group_id');
            $table->dropColumn('group_name');
        });
    }
};
