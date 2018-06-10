<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauthAuthorizationCodesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_authorization_codes', function (Blueprint $table) {
            $table->string('authorization_code')->primary();
            $table->string('client_id');
            $table->unsignedInteger('user_id');
            $table->string('redirect_uri')->nullable();
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
        Schema::drop('oauth_authorization_codes');
    }

}
