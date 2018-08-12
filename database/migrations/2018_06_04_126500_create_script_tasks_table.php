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
            //Id Relations with scripts, services
            $table->integer('object_id')->unsigned();
            $table->enum('script_tasks_type', ['TRIGGER', 'SERVICE'])->default('TRIGGER');

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
