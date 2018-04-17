<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSTEPTRIGGERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('STEP_TRIGGER', function(Blueprint $table)
		{
			$table->string('STEP_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->string('TRI_UID', 32)->default('');
			$table->string('ST_TYPE', 20)->default('');
			$table->string('ST_CONDITION')->default('');
			$table->integer('ST_POSITION')->default(0);
			$table->primary(['STEP_UID','TAS_UID','TRI_UID','ST_TYPE']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('STEP_TRIGGER');
	}

}
