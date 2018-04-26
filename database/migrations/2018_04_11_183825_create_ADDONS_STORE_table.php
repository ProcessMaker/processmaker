<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateADDONSSTORETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ADDONS_STORE', function(Blueprint $table)
		{
			$table->string('STORE_ID', 32)->primary();
			$table->integer('STORE_VERSION')->nullable();
			$table->string('STORE_LOCATION', 2048);
			$table->string('STORE_TYPE');
			$table->dateTime('STORE_LAST_UPDATED')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ADDONS_STORE');
	}

}
