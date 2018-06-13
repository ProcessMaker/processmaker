<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMessageEventDefinitionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_event_definitions', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('message_application_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->text('variables');
            $table->string('correlation', 512)->default('');

            // Setup relationship for Message Application we belong to
            $table->foreign('message_application_id')->references('id')->on('message_applications')->onDelete('cascade');
            // Setup relationship for User  we belong to
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
        Schema::drop('message_event_definitions');
    }

}
