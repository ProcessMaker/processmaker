<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLOGCASESSCHEDULERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LOG_CASES_SCHEDULER', function(Blueprint $table)
		{
			$table->string('LOG_CASE_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->string('USR_NAME', 32)->default('');
			$table->date('EXEC_DATE');
			$table->string('EXEC_HOUR', 32)->default('12:00');
			$table->string('RESULT', 32)->default('SUCCESS');
			$table->string('SCH_UID', 32)->default('OPEN');
			$table->text('WS_CREATE_CASE_STATUS', 16777215);
			$table->text('WS_ROUTE_CASE_STATUS', 16777215);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LOG_CASES_SCHEDULER');
	}

}
