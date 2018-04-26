<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUSRREPORTINGTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('USR_REPORTING', function(Blueprint $table)
		{
			$table->string('USR_UID', 32);
			$table->string('TAS_UID', 32);
			$table->string('PRO_UID', 32);
			$table->integer('MONTH')->default(0);
			$table->integer('YEAR')->default(0);
			$table->decimal('TOTAL_QUEUE_TIME_BY_TASK', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_TIME_BY_TASK', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_IN', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_OUT', 7)->nullable()->default(0.00);
			$table->decimal('USER_HOUR_COST', 7)->nullable()->default(0.00);
			$table->decimal('AVG_TIME', 7)->nullable()->default(0.00);
			$table->decimal('SDV_TIME', 7)->nullable()->default(0.00);
			$table->decimal('CONFIGURED_TASK_TIME', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_OVERDUE', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_ON_TIME', 7)->nullable()->default(0.00);
			$table->decimal('PRO_COST', 7)->nullable()->default(0.00);
			$table->string('PRO_UNIT_COST', 50)->nullable()->default('');
			$table->primary(['USR_UID','TAS_UID','MONTH','YEAR']);
			$table->index(['USR_UID','TAS_UID','PRO_UID'], 'indexReporting');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('USR_REPORTING');
	}

}
