<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestDetailScreenIdToProcess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->unsignedInteger('request_detail_screen_id')->nullable()->after('cancel_screen_id');
            $table->foreign('request_detail_screen_id')->references('id')->on('screens');
        });

        Schema::table('process_versions', function (Blueprint $table) {
            $table->unsignedInteger('request_detail_screen_id')->nullable()->after('cancel_screen_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn('request_detail_screen_id');
        });

        Schema::table('processes', function (Blueprint $table) {
            $table->dropForeign(['request_detail_screen_id']);

            $table->dropColumn('request_detail_screen_id');
        });
    }
}
