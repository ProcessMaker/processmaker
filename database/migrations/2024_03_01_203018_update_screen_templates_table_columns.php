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
            // Update 'user_id' column to be nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Add 'media_collection' column after 'manifest'
            $table->string('media_collection')->after('manifest');

            // Add 'unique_template_id' column after 'uuid'
            $table->string('unique_template_id')->after('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screen_templates', function (Blueprint $table) {
            // Remove the 'unique_template_id' column
            $table->dropColumn('unique_template_id');

            // Remove the 'media_collection' column
            $table->dropColumn('media_collection');

            // Update 'user_id' column to be non-nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
