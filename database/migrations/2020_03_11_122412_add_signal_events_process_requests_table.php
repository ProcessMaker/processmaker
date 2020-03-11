<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSignalEventsProcessRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->json('signal_events')->nullable();
        });
        Schema::table('processes', function (Blueprint $table) {
            $table->json('signal_events')->nullable();
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->json('signal_events')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->dropColumn(['signal_events']);
        });
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn(['signal_events']);
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn(['signal_events']);
        });
    }
}
