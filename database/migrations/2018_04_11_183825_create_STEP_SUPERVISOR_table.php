<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSTEPSUPERVISORTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('STEP_SUPERVISOR', function(Blueprint $table)
		{
			$table->string('STEP_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('0');
			$table->string('STEP_TYPE_OBJ', 20)->default('DYNAFORM');
			$table->string('STEP_UID_OBJ', 32)->default('0');
			$table->integer('STEP_POSITION')->default(0);
			$table->index(['PRO_UID','STEP_TYPE_OBJ','STEP_UID_OBJ'], 'indexStepSupervisor');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('STEP_SUPERVISOR');
	}

}
