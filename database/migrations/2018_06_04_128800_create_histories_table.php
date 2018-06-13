<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHistoriesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('task_id');
            $table->unsignedInteger('process_id');
            $table->unsignedInteger('form_id');
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('user_id');
            $table->text('HISTORY_DATA');
            $table->index(['application_id', 'task_id', 'user_id'], 'indexAppHistory');

            $table->timestamps();

            // Setup relationship for Task we belong to
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for form we belong to
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
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
        Schema::drop('histories');
    }

}
