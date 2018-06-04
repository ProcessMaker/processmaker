<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMESSAGEAPPLICATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('MESSAGE_APPLICATION', function(Blueprint $table)
		{
			$table->string('MSGAPP_UID', 32)->primary();
			$table->string('APP_UID', 32);
			$table->string('PRJ_UID', 32);
			$table->string('EVN_UID_THROW', 32);
			$table->string('EVN_UID_CATCH', 32);
			$table->text('MSGAPP_VARIABLES', 16777215);
			$table->string('MSGAPP_CORRELATION', 512)->default('');
			$table->dateTime('MSGAPP_THROW_DATE');
			$table->dateTime('MSGAPP_CATCH_DATE')->nullable();
			$table->string('MSGAPP_STATUS', 25)->default('UNREAD');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('MESSAGE_APPLICATION');
	}

}
