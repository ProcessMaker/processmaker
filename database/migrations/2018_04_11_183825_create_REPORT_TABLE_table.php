<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateREPORTTABLETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('REPORT_TABLE', function(Blueprint $table)
		{
			$table->string('REP_TAB_UID', 32)->default('')->primary();
			$table->text('REP_TAB_TITLE', 16777215);
			$table->string('PRO_UID', 32)->default('');
			$table->string('REP_TAB_NAME', 100)->default('');
			$table->string('REP_TAB_TYPE', 6)->default('');
			$table->string('REP_TAB_GRID', 150)->nullable()->default('');
			$table->string('REP_TAB_CONNECTION', 32)->default('');
			$table->dateTime('REP_TAB_CREATE_DATE');
			$table->char('REP_TAB_STATUS', 8)->default('ACTIVE');
			$table->index(['PRO_UID','REP_TAB_STATUS'], 'indexProcessStatus');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('REPORT_TABLE');
	}

}
