<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCALENDARDEFINITIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CALENDAR_DEFINITION', function(Blueprint $table)
		{
			$table->string('CALENDAR_UID', 32)->default('')->primary();
			$table->string('CALENDAR_NAME', 100)->default('');
			$table->dateTime('CALENDAR_CREATE_DATE');
			$table->dateTime('CALENDAR_UPDATE_DATE')->nullable();
			$table->string('CALENDAR_WORK_DAYS', 100)->default('');
			$table->text('CALENDAR_DESCRIPTION', 16777215);
			$table->string('CALENDAR_STATUS', 8)->default('ACTIVE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CALENDAR_DEFINITION');
	}

}
