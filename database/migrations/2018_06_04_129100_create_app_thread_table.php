<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppThreadTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('APP_THREAD', function (Blueprint $table) {
            $table->string('APP_UID', 32)->default('');
            $table->integer('APP_THREAD_INDEX')->default(0);
            $table->integer('APP_THREAD_PARENT')->default(0);
            $table->string('APP_THREAD_STATUS', 32)->default('OPEN');
            $table->integer('DEL_INDEX')->default(0);
            $table->primary(['application_id', 'APP_THREAD_INDEX']);

            $table->unsignedInteger('application_id');
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
        Schema::drop('APP_THREAD');
    }

}
