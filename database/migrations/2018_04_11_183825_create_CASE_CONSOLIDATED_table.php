<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCASECONSOLIDATEDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CASE_CONSOLIDATED', function(Blueprint $table)
		{
			$table->string('TAS_UID', 32)->default('')->primary();
			$table->string('DYN_UID', 32)->default('');
			$table->string('REP_TAB_UID', 32)->default('');
			$table->string('CON_STATUS', 20)->default('ACTIVE')->index('indexConStatus');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CASE_CONSOLIDATED');
	}

}
