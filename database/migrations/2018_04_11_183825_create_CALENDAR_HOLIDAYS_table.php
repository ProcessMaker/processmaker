<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCALENDARHOLIDAYSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CALENDAR_HOLIDAYS', function(Blueprint $table)
		{
			$table->string('CALENDAR_UID', 32)->default('');
			$table->string('CALENDAR_HOLIDAY_NAME', 100)->default('');
			$table->dateTime('CALENDAR_HOLIDAY_START');
			$table->dateTime('CALENDAR_HOLIDAY_END');
			$table->primary(['CALENDAR_UID','CALENDAR_HOLIDAY_NAME']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CALENDAR_HOLIDAYS');
	}

}
