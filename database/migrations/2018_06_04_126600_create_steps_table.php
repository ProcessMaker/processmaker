<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStepsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('task_id')->unsigned();

            $table->integer('object_id')->unsigned();
            $table->string('steps_type', 20)->default('DYNAFORM');

            $table->text('condition');
            $table->integer('position')->default(0);
            $table->string('mode', 10)->default('EDIT');

            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for Task we belong to
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('steps');
    }

}
