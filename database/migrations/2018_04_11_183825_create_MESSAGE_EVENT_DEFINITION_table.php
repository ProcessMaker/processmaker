<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMESSAGEEVENTDEFINITIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('MESSAGE_EVENT_DEFINITION', function(Blueprint $table)
		{
			$table->string('MSGED_UID', 32)->primary();
			$table->string('PRJ_UID', 32);
			$table->string('EVN_UID', 32);
			$table->string('MSGT_UID', 32)->default('');
			$table->string('MSGED_USR_UID', 32)->default('');
			$table->text('MSGED_VARIABLES', 16777215);
			$table->string('MSGED_CORRELATION', 512)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('MESSAGE_EVENT_DEFINITION');
	}

}
