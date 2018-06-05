<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to update the process design tables to version 4.0.0.
 *
 */
class UpdateVariousTables extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update process table to have foreign keys
        Schema::table('processes', function (Blueprint $table) {
            $table->foreign('create_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
            $table->foreign('open_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
            $table->foreign('deleted_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
            $table->foreign('canceled_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
            $table->foreign('paused_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
            $table->foreign('reassigned_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
            $table->foreign('unpaused_trigger_id')->references('id')->on('triggers')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Delete process table to have foreign keys
        Schema::table('processes', function (Blueprint $table) {
            $table->dropForeign('processes_create_trigger_id_foreign');
            $table->dropForeign('processes_open_trigger_id_foreign');
            $table->dropForeign('processes_deleted_trigger_id_foreign');
            $table->dropForeign('processes_canceled_trigger_id_foreign');
            $table->dropForeign('processes_paused_trigger_id_foreign');
            $table->dropForeign('processes_reassigned_trigger_id_foreign');
            $table->dropForeign('processes_unpaused_trigger_id_foreign');
        });

    }
}
