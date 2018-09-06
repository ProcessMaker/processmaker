<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_files', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('process_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->unsignedInteger('update_user_id')->nullable();
            $table->string('path', 256)->default('');
            $table->string('type', 32)->default('');
            $table->boolean('editable')->default(true);
            $table->string('drive', 32);
            $table->string('path_for_client');
            $table->timestamps();

            // Setup relationship for process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('update_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_files');
    }
}
