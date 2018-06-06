<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMessageEventRelationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_event_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('throw_event_id')->unsigned();
            $table->integer('catch_event_id')->unsigned();

            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for Event we belong to
            $table->foreign('throw_event_id')->references('id')->on('events')->onDelete('cascade');
            // Setup relationship for Event we belong to
            $table->foreign('catch_event_id')->references('id')->on('events')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('message_event_relations');
    }

}
