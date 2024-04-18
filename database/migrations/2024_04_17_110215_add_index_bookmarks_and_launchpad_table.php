<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_process_bookmarks', function (Blueprint $table) {
            // Create index
            $table->index(['process_id', 'user_id']);
        });
        Schema::table('process_launchpad', function (Blueprint $table) {
            // Create index
            $table->index('process_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_process_bookmarks', function (Blueprint $table) {
            // Drop index
            $table->dropIndex(['process_id', 'user_id']);
        });
        Schema::table('process_launchpad', function (Blueprint $table) {
            // Drop index
            $table->dropIndex(['process_id']);
        });
    }
};
