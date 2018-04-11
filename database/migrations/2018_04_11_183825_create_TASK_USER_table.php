<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTASKUSERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('TASK_USER', function(Blueprint $table)
		{
			$table->string('TAS_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->integer('TU_TYPE')->default(1);
			$table->integer('TU_RELATION')->default(0);
			$table->primary(['TAS_UID','USR_UID','TU_TYPE','TU_RELATION']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('TASK_USER');
	}

}
