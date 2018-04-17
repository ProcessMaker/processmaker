<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOAUTHCLIENTSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('OAUTH_CLIENTS', function(Blueprint $table)
		{
			$table->string('CLIENT_ID', 80)->primary();
			$table->string('CLIENT_SECRET', 80);
			$table->string('CLIENT_NAME', 256);
			$table->string('CLIENT_DESCRIPTION', 1024);
			$table->string('CLIENT_WEBSITE', 1024);
			$table->string('REDIRECT_URI', 2000);
			$table->string('USR_UID', 32);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('OAUTH_CLIENTS');
	}

}
