<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateGroupUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('GROUP_USER', function (Blueprint $table) {
            $table->dropPrimary(['GRP_UID','USR_UID']);
            $table->integer('GRP_ID')->first();
            $table->integer('USR_ID')->after('GRP_ID');
            $table->dropColumn('GRP_UID');
            $table->dropColumn('USR_UID');
            $table->primary(['GRP_ID','USR_ID']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('GROUP_USER', function (Blueprint $table) {
            $table->string('GRP_UID', 32)->first();
            $table->string('USR_UID', 32)->after('GRP_UID');
            $table->dropColumn('GRP_ID');
            $table->dropColumn('USR_ID');
            $table->primary(['GRP_UID','USR_UID']);
        });
    }
}
