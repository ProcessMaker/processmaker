<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOAUTHACCESSTOKENSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('OAUTH_ACCESS_TOKENS', function(Blueprint $table)
		{
			$table->string('ACCESS_TOKEN')->primary();
			$table->string('CLIENT_ID', 80);
			$table->string('USER_ID', 32)->nullable();
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
		Schema::drop('OAUTH_ACCESS_TOKENS');
	}

}
