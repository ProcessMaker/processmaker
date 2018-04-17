<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePMOAUTHUSERACCESSTOKENSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PMOAUTH_USER_ACCESS_TOKENS', function(Blueprint $table)
		{
			$table->string('ACCESS_TOKEN', 40)->primary();
			$table->string('REFRESH_TOKEN', 40);
			$table->string('USER_ID', 32)->nullable();
			$table->string('SESSION_ID', 64);
			$table->string('SESSION_NAME', 64);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PMOAUTH_USER_ACCESS_TOKENS');
	}

}
