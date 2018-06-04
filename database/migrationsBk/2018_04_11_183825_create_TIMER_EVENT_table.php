<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTIMEREVENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('TIMER_EVENT', function(Blueprint $table)
		{
			$table->string('TMREVN_UID', 32)->primary();
			$table->string('PRJ_UID', 32);
			$table->string('EVN_UID', 32);
			$table->string('TMREVN_OPTION', 50)->default('DAILY');
			$table->date('TMREVN_START_DATE')->nullable();
			$table->date('TMREVN_END_DATE')->nullable();
			$table->string('TMREVN_DAY', 5)->default('');
			$table->string('TMREVN_HOUR', 5)->default('');
			$table->string('TMREVN_MINUTE', 5)->default('');
			$table->text('TMREVN_CONFIGURATION_DATA', 16777215);
			$table->dateTime('TMREVN_NEXT_RUN_DATE')->nullable();
			$table->dateTime('TMREVN_LAST_RUN_DATE')->nullable();
			$table->dateTime('TMREVN_LAST_EXECUTION_DATE')->nullable();
			$table->string('TMREVN_STATUS', 25)->default('ACTIVE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('TIMER_EVENT');
	}

}
