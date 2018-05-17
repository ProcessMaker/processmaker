<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ProcessMaker\Model\Group;

class CreateGROUPWFTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid');
            $table->string('title', 1000);
            $table->char('status', 8)->default(Group::STATUS_ACTIVE);
            $table->string('ldap_dn')->default('');
            $table->string('ux', 128)->nullable()->default(GROUP::UX_NORMAL);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('groups');
    }

}
