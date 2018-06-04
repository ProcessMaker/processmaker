<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauthClientsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('secret');
            $table->string('name');
            $table->string('description');
            $table->string('website');
            $table->string('redirect_uri');
            $table->unsignedInteger('creator_user_id');

            // setup relationship for User we belong to
            $table->foreign('creator_user_id')->references('id')->on('users')->ondelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('oauth_clients');
    }

}
