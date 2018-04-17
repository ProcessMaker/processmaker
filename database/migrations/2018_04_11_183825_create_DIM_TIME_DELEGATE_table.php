<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDIMTIMEDELEGATETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DIM_TIME_DELEGATE', function(Blueprint $table)
		{
			$table->string('TIME_ID', 10)->default('')->primary();
			$table->integer('MONTH_ID')->default(0);
			$table->integer('QTR_ID')->default(0);
			$table->integer('YEAR_ID')->default(0);
			$table->string('MONTH_NAME', 3)->default('0');
			$table->string('MONTH_DESC', 9)->default('');
			$table->string('QTR_NAME', 4)->default('');
			$table->string('QTR_DESC', 9)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DIM_TIME_DELEGATE');
	}

}
