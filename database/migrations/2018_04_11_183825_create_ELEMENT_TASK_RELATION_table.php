<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateELEMENTTASKRELATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ELEMENT_TASK_RELATION', function(Blueprint $table)
		{
			$table->string('ETR_UID', 32)->primary();
			$table->string('PRJ_UID', 32);
			$table->string('ELEMENT_UID', 32);
			$table->string('ELEMENT_TYPE', 50)->default('');
			$table->string('TAS_UID', 32);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ELEMENT_TASK_RELATION');
	}

}
