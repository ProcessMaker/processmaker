<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMESSAGEEVENTRELATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('MESSAGE_EVENT_RELATION', function(Blueprint $table)
		{
			$table->string('MSGER_UID', 32)->primary();
			$table->string('PRJ_UID', 32);
			$table->string('EVN_UID_THROW', 32);
			$table->string('EVN_UID_CATCH', 32);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('MESSAGE_EVENT_RELATION');
	}

}
