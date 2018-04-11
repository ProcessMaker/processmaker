<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOAUTHSCOPESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('OAUTH_SCOPES', function(Blueprint $table)
		{
			$table->string('TYPE', 40);
			$table->string('SCOPE', 2000)->nullable();
			$table->string('CLIENT_ID', 80)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('OAUTH_SCOPES');
	}

}
