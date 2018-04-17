<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateREPORTVARTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('REPORT_VAR', function(Blueprint $table)
		{
			$table->string('REP_VAR_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->string('REP_TAB_UID', 32)->default('');
			$table->string('REP_VAR_NAME')->default('');
			$table->string('REP_VAR_TYPE', 20)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('REPORT_VAR');
	}

}
