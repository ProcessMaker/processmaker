<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSubProcessesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_processes', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('task_id')->unsigned();
            $table->integer('parent_task_id')->unsigned();

            $table->string('type', 20)->default('');
            $table->integer('synchronous')->default(0);
            $table->string('synchronous_type', 20)->default('');
            $table->integer('synchronous_wait')->default(0);
            $table->text('variables_out')->nullable();
            $table->text('variables_in')->nullable();
            $table->string('grid_in', 50)->default('');

            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for Task we belong to
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            // Setup relationship for Task we belong to
            $table->foreign('parent_task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_processes');
    }

}
