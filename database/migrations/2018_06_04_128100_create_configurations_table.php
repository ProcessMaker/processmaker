<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateConfigurationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->string('parameter', 128)->default('');
            $table->text('value')->nullable();
            $table->integer('user_id')->nullable()->unsigned();
            $table->integer('process_id')->nullable()->unsigned();
            $table->integer('application_id')->nullable()->unsigned();

            // Setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
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
        Schema::drop('configurations');
    }

}
