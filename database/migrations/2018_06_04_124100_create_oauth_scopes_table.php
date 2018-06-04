<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauthScopesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_scopes', function (Blueprint $table) {
            $table->string('type');
            $table->string('scope')->nullable();
            $table->string('client_id')->nullable();

            // setup relationship for Oauth Clients we belong to
            $table->foreign('client_id')->references('id')->on('oauth_clients')->ondelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('oauth_scopes');
    }

}
