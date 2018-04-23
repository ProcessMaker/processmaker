<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateInputDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('INPUT_DOCUMENT', function (Blueprint $table) {
            $table->dropPrimary('INP_DOC_UID');
            $table->integer('INP_DOC_ID')->first();
            $table->integer('PRO_ID')->after('INP_DOC_UID');
            $table->primary('INP_DOC_ID');
            $table->foreign('PRO_ID', 'fk_bpmn_input_doc_process')->references('PRO_ID')->on('PROCESS')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
        DB::statement('ALTER TABLE INPUT_DOCUMENT MODIFY INP_DOC_ID INTEGER NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('INPUT_DOCUMENT', function (Blueprint $table) {
            $table->dropForeign('fk_bpmn_input_doc_process');
            $table->dropColumn('INP_DOC_ID');
            $table->dropColumn('PRO_ID');
            $table->primary('INP_DOC_UID');
        });
    }
}
