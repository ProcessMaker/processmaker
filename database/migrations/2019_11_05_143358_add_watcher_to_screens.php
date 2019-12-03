<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWatcherToScreens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->text('watchers')->nullable();
        });

        Schema::table('screen_versions', function (Blueprint $table) {
            $table->text('watchers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->dropColumn('watchers');
        });

        Schema::table('screens', function (Blueprint $table) {
            $table->dropColumn('watchers');
        });
    }
}
