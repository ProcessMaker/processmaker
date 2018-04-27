<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOutputDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('OUTPUT_DOCUMENT', function (Blueprint $table) {
            $table->dropPrimary('OUT_DOC_UID');
            $table->integer('OUT_DOC_ID')->first();
            $table->string('OUT_DOC_PDF_SECURITY_OPEN_PASSWORD', 256)->change();
            $table->string('OUT_DOC_PDF_SECURITY_OWNER_PASSWORD', 256)->change();
            $table->primary('OUT_DOC_ID');
        });
        DB::statement('ALTER TABLE OUTPUT_DOCUMENT MODIFY OUT_DOC_ID INTEGER NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('OUTPUT_DOCUMENT', function (Blueprint $table) {
            $table->dropColumn('OUT_DOC_ID');
            $table->primary('OUT_DOC_UID');
            $table->string('OUT_DOC_PDF_SECURITY_OPEN_PASSWORD', 32)->change();
            $table->string('OUT_DOC_PDF_SECURITY_OWNER_PASSWORD', 32)->change();
        });
    }

}
