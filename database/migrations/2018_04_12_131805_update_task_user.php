<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTaskUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('TASK_USER', function (Blueprint $table) {
            $table->dropPrimary(['TAS_UID','USR_UID','TU_TYPE','TU_RELATION']);
            $table->integer('TASK_USER_ID')->first();
            $table->integer('TAS_ID')->after('TASK_USER_ID');
            $table->integer('USR_ID')->after('TAS_UID');
            $table->string('TU_RELATION', 10)->change();
            $table->primary('TASK_USER_ID');
        });
        DB::statement('ALTER TABLE TASK_USER MODIFY TASK_USER_ID INTEGER NOT NULL AUTO_INCREMENT');

        Schema::table('GROUPWF', function (Blueprint $table) {
            $table->dropPrimary('GRP_UID');
            $table->integer('GRP_ID')->first();
            $table->primary('GRP_ID');
        });
        DB::statement('ALTER TABLE GROUPWF MODIFY GRP_ID INTEGER NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('TASK_USER', function (Blueprint $table) {
            $table->dropColumn('TAS_ID');
            $table->dropColumn('USR_ID');
            $table->integer('TU_RELATION')->change();
            $table->dropColumn('TASK_USER_ID');
            $table->primary(['TAS_UID','USR_UID','TU_TYPE','TU_RELATION']);
        });

        Schema::table('GROUPWF', function (Blueprint $table) {
            $table->dropColumn('GRP_ID');
            $table->primary('GRP_UID');
        });
    }
}
