<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLANGUAGETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LANGUAGE', function(Blueprint $table)
		{
			$table->string('LAN_ID', 4)->default('')->primary();
			$table->string('LAN_LOCATION', 4)->default('');
			$table->string('LAN_NAME', 30)->default('');
			$table->string('LAN_NATIVE_NAME', 30)->default('');
			$table->char('LAN_DIRECTION', 1)->default('L');
			$table->integer('LAN_WEIGHT')->default(0);
			$table->char('LAN_ENABLED', 1)->default(1);
			$table->string('LAN_CALENDAR', 30)->default('GREGORIAN');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LANGUAGE');
	}

}
