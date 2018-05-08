<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOAUTHAUTHORIZATIONCODESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('oauth_authorization_codes', function(Blueprint $table)
		{
			$table->string('authorization_code')->primary();
			$table->string('client_id');
			$table->unsignedInteger('user_id');
			$table->string('redirect_uri')->nullable();
			$table->dateTime('expires');
			$table->string('scope')->nullable();
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
