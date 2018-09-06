<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateObjectPermissionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('object_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('task_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->integer('user_relation')->default(0);
            $table->string('task_source', 32)->nullable()->default('0');
            $table->integer('participate')->default(0);
            $table->string('obj_type', 15)->default('0');
            $table->string('obj_uid', 32)->default('0');
            $table->string('action', 10)->default('0');
            $table->string('case_status', 10)->nullable()->default('0');
            $table->index(['process_id', 'task_id', 'user_id', 'task_source', 'obj_uid'], 'indexObjectPermission');

            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for Task we belong to
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('object_permissions');
    }

}
