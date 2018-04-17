<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCASETRACKERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CASE_TRACKER', function(Blueprint $table)
		{
			$table->string('PRO_UID', 32)->default('0')->primary();
			$table->string('CT_MAP_TYPE', 10)->default('0');
			$table->integer('CT_DERIVATION_HISTORY')->default(0);
			$table->integer('CT_MESSAGE_HISTORY')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CASE_TRACKER');
	}

}
