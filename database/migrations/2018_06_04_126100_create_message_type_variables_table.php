<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMessageTypeVariablesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_type_variables', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('message_type_id')->unsigned();
            $table->string('name', 512)->default('');
            $table->string('default_value', 512)->default('');
            $table->timestamps();

            // Setup relationship for Process we belong to
            $table->foreign('message_type_id')->references('id')->on('message_types')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('message_type_variables');
    }

}
