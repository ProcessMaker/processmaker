<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\ProcessRequest;

class AddSignalEventsProcessRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
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
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
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
