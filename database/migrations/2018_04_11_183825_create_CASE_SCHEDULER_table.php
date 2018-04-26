<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCASESCHEDULERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CASE_SCHEDULER', function(Blueprint $table)
		{
			$table->string('SCH_UID', 32)->primary();
			$table->string('SCH_DEL_USER_NAME', 100);
			$table->string('SCH_DEL_USER_PASS', 100);
			$table->string('SCH_DEL_USER_UID', 100);
			$table->string('SCH_NAME', 100);
			$table->string('PRO_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->dateTime('SCH_TIME_NEXT_RUN');
			$table->dateTime('SCH_LAST_RUN_TIME')->nullable();
			$table->string('SCH_STATE', 15)->default('ACTIVE');
			$table->string('SCH_LAST_STATE', 60)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->boolean('SCH_OPTION')->default(0);
			$table->dateTime('SCH_START_TIME');
			$table->dateTime('SCH_START_DATE');
			$table->char('SCH_DAYS_PERFORM_TASK', 5)->default('');
			$table->boolean('SCH_EVERY_DAYS')->nullable()->default(0);
			$table->char('SCH_WEEK_DAYS', 14)->default('0|0|0|0|0|0|0');
			$table->char('SCH_START_DAY', 6)->default('');
			$table->char('SCH_MONTHS', 27)->default('0|0|0|0|0|0|0|0|0|0|0|0');
			$table->dateTime('SCH_END_DATE')->nullable();
			$table->string('SCH_REPEAT_EVERY', 15)->default('');
			$table->string('SCH_REPEAT_UNTIL', 15)->default('');
			$table->boolean('SCH_REPEAT_STOP_IF_RUNNING')->nullable()->default(0);
			$table->dateTime('SCH_EXECUTION_DATE')->nullable();
			$table->string('CASE_SH_PLUGIN_UID', 100)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CASE_SCHEDULER');
	}

}
