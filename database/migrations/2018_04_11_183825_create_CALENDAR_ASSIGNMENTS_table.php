<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCALENDARASSIGNMENTSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CALENDAR_ASSIGNMENTS', function(Blueprint $table)
		{
			$table->string('OBJECT_UID', 32)->default('')->primary();
			$table->string('CALENDAR_UID', 32)->default('');
			$table->string('OBJECT_TYPE', 100)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CALENDAR_ASSIGNMENTS');
	}

}
