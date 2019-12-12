<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSelfServiceTasksToProcesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->json('self_service_tasks')->nullable();
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->json('self_service_tasks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn(['self_service_tasks']);
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn(['self_service_tasks']);
        });
    }
}
