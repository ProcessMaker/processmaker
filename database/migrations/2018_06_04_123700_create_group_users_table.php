<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_users', function (Blueprint $table) {
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('user_id')->index('indexForUsrUid');
            $table->primary(['group_id', 'user_id']);

            // setup relationship for group we belong to
            $table->foreign('group_id')->references('id')->on('groups')->ondelete('cascade');
            // setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_users');
    }
}
