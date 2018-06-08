<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEventsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('application_id')->unsigned();
            $table->integer('index')->default(0);
            $table->dateTime('action_date');
            $table->boolean('attempts')->default(false);
            $table->dateTime('last_execution_date')->nullable();
            $table->string('status', 32)->default('OPEN');

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
        Schema::drop('events');
    }

}
