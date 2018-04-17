<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSCRIPTTASKTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SCRIPT_TASK', function(Blueprint $table)
		{
			$table->string('SCRTAS_UID', 32)->default('')->primary();
			$table->string('PRJ_UID', 32)->default('');
			$table->string('ACT_UID', 32)->default('');
			$table->string('SCRTAS_OBJ_TYPE', 10)->default('TRIGGER');
			$table->string('SCRTAS_OBJ_UID', 32)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SCRIPT_TASK');
	}

}
