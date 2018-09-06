<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateExecuteTimeoutActionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('execute_timeout_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('application_id');
            $table->integer('index')->default(0);
            $table->dateTime('execution_date')->nullable();

            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('execute_timeout_actions');
    }

}
