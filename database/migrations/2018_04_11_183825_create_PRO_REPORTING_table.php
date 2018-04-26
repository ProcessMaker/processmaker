<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROREPORTINGTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PRO_REPORTING', function(Blueprint $table)
		{
			$table->string('PRO_UID', 32);
			$table->integer('MONTH')->default(0);
			$table->integer('YEAR')->default(0);
			$table->decimal('AVG_TIME', 7)->nullable()->default(0.00);
			$table->decimal('SDV_TIME', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_IN', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_OUT', 7)->nullable()->default(0.00);
			$table->decimal('CONFIGURED_PROCESS_TIME', 7)->nullable()->default(0.00);
			$table->decimal('CONFIGURED_PROCESS_COST', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_OPEN', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_OVERDUE', 7)->nullable()->default(0.00);
			$table->decimal('TOTAL_CASES_ON_TIME', 7)->nullable()->default(0.00);
			$table->decimal('PRO_COST', 7)->nullable()->default(0.00);
			$table->string('PRO_UNIT_COST', 50)->nullable()->default('');
			$table->primary(['PRO_UID','MONTH','YEAR']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PRO_REPORTING');
	}

}
