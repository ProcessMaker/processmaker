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
            // Drop the foreign key constraint for the 'user_id' column
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screen_templates', function (Blueprint $table) {
            // Add back the foreign key constraint for the 'user_id' column
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
