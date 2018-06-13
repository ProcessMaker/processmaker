<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauthAccessTokensTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->string('access_token')->primary();
            $table->string('client_id');
            // If user_id is null, then this is client authentication
            $table->unsignedInteger('user_id')->nullable();
            $table->dateTime('expires');
            $table->string('scope')->nullable();

            // setup relationship for Oauth Client we belong to
            $table->foreign('client_id')->references('id')->on('oauth_clients')->ondelete('cascade');
            // setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->ondelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('oauth_access_tokens');
    }

}
