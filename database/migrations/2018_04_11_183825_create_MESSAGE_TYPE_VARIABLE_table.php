<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMESSAGETYPEVARIABLETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('MESSAGE_TYPE_VARIABLE', function(Blueprint $table)
		{
			$table->string('MSGTV_UID', 32)->primary();
			$table->string('MSGT_UID', 32);
			$table->string('MSGTV_NAME', 512)->nullable()->default('');
			$table->string('MSGTV_DEFAULT_VALUE', 512)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('MESSAGE_TYPE_VARIABLE');
	}

}
