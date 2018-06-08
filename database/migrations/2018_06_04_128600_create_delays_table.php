<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDelaysTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delays', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('task_id');


            $table->integer('number')->nullable()->default(0)->index('INDEX_NUMBER');
            $table->integer('thread_index')->default(0);
            $table->integer('del_index')->default(0);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->unsignedInteger('enable_user_id')->nullable();
            $table->unsignedInteger('disable_user_id')->nullable();
            $table->unsignedInteger('delegation_user_id')->nullable();

            $table->dateTime('enable_action_date')->nullable();
            $table->dateTime('disable_action_date')->nullable();
            $table->dateTime('automatic_action_date')->nullable();

            $table->timestamps();

            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('enable_user_id')->references('id')->on('users')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('disable_user_id')->references('id')->on('users')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('delegation_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('delays');
    }

}
