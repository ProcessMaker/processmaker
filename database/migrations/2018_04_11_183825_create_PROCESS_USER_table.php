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
			$table->uuid('PU_UID')->default('')->primary();
			$table->uuid('PRO_UID');
			$table->uuid('USR_UID');
			$table->string('PU_TYPE', 20);
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
