<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateElementTaskRelationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('element_task_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('task_id')->unsigned();
            $table->integer('element_id')->unsigned();
            $table->string('element_type', 50)->default('');

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
        Schema::drop('element_task_relations');
    }

}
