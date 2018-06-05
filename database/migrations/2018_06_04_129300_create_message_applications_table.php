<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMessageApplicationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('application_id')->unsigned();
            $table->text('variables');
            $table->string('correlation', 512)->default('');
            $table->dateTime('throw_date');
            $table->dateTime('catch_date')->nullable();
            $table->enum('status', ['READ', 'UNREAD'])->default('UNREAD');
            $table->timestamps();

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
        Schema::drop('message_applications');
    }

}
