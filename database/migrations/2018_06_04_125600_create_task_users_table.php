<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ProcessMaker\Model\User;

class CreateTaskUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_users', function (Blueprint $table) {
            $table->unsignedInteger('task_id');
            $table->unsignedInteger('user_id');
            $table->integer('type')->default(1);
            $table->string('task_users_type')->default(User::TYPE);
            $table->primary(['task_id', 'user_id', 'type', 'task_users_type']);

            // setup relationship for task we belong to
            $table->foreign('task_id')->references('id')->on('tasks')->ondelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('task_users');
    }

}
