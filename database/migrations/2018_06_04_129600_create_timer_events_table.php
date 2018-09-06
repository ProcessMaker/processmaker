<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimerEventsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timer_events', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('event_id')->unsigned();
            $table->string('option', 50)->default('DAILY');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('day', 5)->default('');
            $table->string('hour', 5)->default('');
            $table->string('minute', 5)->default('');
            $table->text('configuration_data');
            $table->dateTime('next_run_date')->nullable();
            $table->dateTime('last_run_date')->nullable();
            $table->dateTime('last_execution_date')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for Event we belong to
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('timer_events');
    }

}
