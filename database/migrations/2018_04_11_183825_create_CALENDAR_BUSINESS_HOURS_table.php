<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCALENDARBUSINESSHOURSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CALENDAR_BUSINESS_HOURS', function(Blueprint $table)
		{
			$table->string('CALENDAR_UID', 32)->default('');
			$table->string('CALENDAR_BUSINESS_DAY', 10)->default('');
			$table->string('CALENDAR_BUSINESS_START', 10)->default('');
			$table->string('CALENDAR_BUSINESS_END', 10)->default('');
			$table->primary(['CALENDAR_UID','CALENDAR_BUSINESS_DAY','CALENDAR_BUSINESS_START','CALENDAR_BUSINESS_END'], 'primaryKey');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CALENDAR_BUSINESS_HOURS');
	}

}
