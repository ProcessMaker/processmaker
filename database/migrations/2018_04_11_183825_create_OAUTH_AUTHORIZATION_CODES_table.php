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
		Schema::create('OAUTH_AUTHORIZATION_CODES', function(Blueprint $table)
		{
			$table->string('AUTHORIZATION_CODE')->primary();
			$table->string('CLIENT_ID', 80);
			$table->string('USER_ID', 32)->nullable();
			$table->string('REDIRECT_URI', 2000)->nullable();
			$table->dateTime('EXPIRES');
			$table->string('SCOPE', 2000)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('OAUTH_AUTHORIZATION_CODES');
	}

}
