<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateScriptTasksTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('script_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('tasks_id')->unsigned();
            $table->integer('object_id')->unsigned();
            $table->string('script_tasks_type', 10)->default('TRIGGER');

            // Setup relationship for Tasks we belong to
            $table->foreign('tasks_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('script_tasks');
    }

}
