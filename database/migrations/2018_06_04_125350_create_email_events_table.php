<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEmailEventsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('EMAIL_EVENT', function (Blueprint $table) {
            $table->integer('EMAIL_EVENT_ID', true);
            $table->string('EMAIL_EVENT_UID', 32);
            $table->string('EVN_UID', 32);
            $table->string('EMAIL_EVENT_FROM', 100)->default('');
            $table->text('EMAIL_EVENT_TO', 16777215);
            $table->string('EMAIL_EVENT_SUBJECT')->nullable()->default('');
            $table->unsignedInteger('process_file_id')->nullable();
            $table->string('EMAIL_SERVER_UID', 32)->nullable()->default('');
            $table->dateTime('EMAIL_EVENT_CREATE');
            $table->dateTime('EMAIL_EVENT_UPDATE');

            $table->integer('process_id')->unsigned();

            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('EMAIL_EVENT');
    }

}
