<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMESSAGETYPETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('MESSAGE_TYPE', function(Blueprint $table)
		{
			$table->string('MSGT_UID', 32)->primary();
			$table->string('PRJ_UID', 32);
			$table->string('MSGT_NAME', 512)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('MESSAGE_TYPE');
	}

}
