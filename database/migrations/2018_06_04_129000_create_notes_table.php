<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNotesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('user_id');

            $table->text('CONTENT');
            $table->enum('TYPE', ['USER'])->default('USER');
            $table->enum('AVAILABILITY', ['PRIVATE', 'PUBLIC'])->default('PUBLIC');
            $table->text('RECIPIENTS')->nullable();
            $table->index(['application_id', 'user_id'], 'indexAppNotesUser');

            $table->timestamps();

            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notes');
    }

}
