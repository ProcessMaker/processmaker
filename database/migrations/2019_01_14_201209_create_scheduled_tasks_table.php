<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('process_id')->nullable(); // could be a system-generated comment
            $table->unsignedInteger('process_request_id')->nullable(); // could be a system-generated comment
            $table->unsignedInteger('process_request_token_id')->nullable(); // could be a system-generated comment
            $table->string('type', 255);
            $table->dateTime('last_execution')->nullable();
            $table->text('configuration');
            $table->timestamps();

            $table->index('process_id');
            $table->index('process_request_id');
            $table->index('process_request_token_id');

            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            $table->foreign('process_request_token_id')->references('id')->on('process_request_tokens')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduled_tasks');
    }
}
