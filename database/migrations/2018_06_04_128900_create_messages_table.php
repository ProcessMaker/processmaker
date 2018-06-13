<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMessagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('task_id')->nullable();
            $table->integer('index')->default(0);
            $table->string('subject', 150)->default('');
            $table->string('from', 100)->default('');
            $table->text('to');
            $table->text('body');
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->text('template')->nullable();
            $table->text('attach')->nullable();
            $table->dateTime('send_date');
            $table->boolean('show_message')->default(true);
            $table->text('error')->nullable();
            $table->enum('status', ['SEND', 'ERROR', 'SUCCESS'])->default('SEND')->index('indexForMsgStatus');

            $table->timestamps();

            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
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
        Schema::drop('messages');
    }

}
