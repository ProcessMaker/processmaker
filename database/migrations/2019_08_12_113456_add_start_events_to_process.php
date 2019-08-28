<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\Process;

class AddStartEventsToProcess extends Migration
{
    /**
     * Add column to store start events to speed up Start Request list
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->json('start_events');
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->json('start_events');
        });
        foreach(Process::all() as $process) {
            $process->start_events = $process->getUpdatedStartEvents();
            $process->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn('start_events');
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn('start_events');
        });
    }
}
