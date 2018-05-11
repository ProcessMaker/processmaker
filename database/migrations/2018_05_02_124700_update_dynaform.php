<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDynaform extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('DYNAFORM', function (Blueprint $table) {
            $table->dropPrimary('DYN_UID');
            $table->integer('DYN_ID')->first();
            $table->primary('DYN_ID');
            $table->dropColumn('DYN_VERSION');
            $table->dropColumn('DYN_FILENAME');
            $table->dropColumn('DYN_TYPE');
        });
        DB::statement('ALTER TABLE DYNAFORM MODIFY DYN_ID INTEGER NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('DYNAFORM', function (Blueprint $table) {
            $table->dropColumn('DYN_ID');
            $table->primary('DYN_UID');
            $table->integer('DYN_VERSION');
            $table->string('DYN_FILENAME', 100);
            $table->string('DYN_TYPE', 20);
        });
    }
}
