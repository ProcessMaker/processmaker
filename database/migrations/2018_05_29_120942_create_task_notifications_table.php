<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid');
            $table->unsignedInteger('task_id');

            //task description
            $table->unsignedInteger('email_server_id')->nullable();
            $table->enum('type', ['AFTER_ROUTING', 'RECEIVE'])->default('AFTER_ROUTING');
            $table->boolean('receive_last_email')->default(true);
            $table->boolean('receive_email_from_format')->default(false);
            $table->text('receive_subject_message')->nullable();
            $table->text('receive_message')->nullable();
            $table->string('receive_message_template')->default('alert_message.html');
            $table->enum('receive_message_type', ['TEXT', 'TEMPLATE'])->default('TEXT');
            $table->timestamps();

            // setup relationships of the task_notifications with tasks and other tables
            $table->foreign('task_id')->references('id')->on('tasks')->ondelete('cascade');
            // setup relationships of the task_notifications with email_servers and other tables
            $table->foreign('email_server_id')->references('id')->on('email_servers')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_notifications');
    }
}
