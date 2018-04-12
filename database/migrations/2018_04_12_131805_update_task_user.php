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
            $table->integer('TAS_ID')->first();
            $table->integer('USR_ID')->after('TAS_UID');
            $table->string('TU_RELATION', 150)->change();
        });
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
        });
    }
}
