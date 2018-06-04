<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPTIMEOUTACTIONEXECUTEDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_TIMEOUT_ACTION_EXECUTED', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('')->primary();
			$table->integer('DEL_INDEX')->default(0);
			$table->dateTime('EXECUTION_DATE')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_TIMEOUT_ACTION_EXECUTED');
	}

}
