<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

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
            $table->foreign('create_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
            $table->foreign('open_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
            $table->foreign('deleted_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
            $table->foreign('canceled_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
            $table->foreign('paused_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
            $table->foreign('reassigned_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
            $table->foreign('unpaused_script_id')->references('id')->on('scripts')->onDelete('CASCADE');
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
            $table->dropForeign('processes_create_script_id_foreign');
            $table->dropForeign('processes_open_script_id_foreign');
            $table->dropForeign('processes_deleted_script_id_foreign');
            $table->dropForeign('processes_canceled_script_id_foreign');
            $table->dropForeign('processes_paused_script_id_foreign');
            $table->dropForeign('processes_reassigned_script_id_foreign');
            $table->dropForeign('processes_unpaused_script_id_foreign');
        });

    }
}
