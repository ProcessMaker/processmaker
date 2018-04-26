<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSUSERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PROCESS_USER', function(Blueprint $table)
		{
			$table->string('PU_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->string('PU_TYPE', 20)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PROCESS_USER');
	}

}
