<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTriggers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('TRIGGERS', function (Blueprint $table) {
            $table->dropPrimary('TRI_UID');
            $table->integer('TRI_ID')->first();
            $table->integer('PRO_ID')->after('TRI_DESCRIPTION');
            $table->primary('TRI_ID');
            $table->foreign('PRO_ID', 'fk_bpmn_triggers_process')->references('PRO_ID')->on('PROCESS')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
        DB::statement('ALTER TABLE TRIGGERS MODIFY TRI_ID INTEGER NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('TRIGGERS', function (Blueprint $table) {
            $table->dropForeign('fk_bpmn_triggers_process');
            $table->dropColumn('TRI_ID');
            $table->dropColumn('PRO_ID');
            $table->primary('TRI_UID');
        });
    }
}
