<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSTEPTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('STEP', function(Blueprint $table)
		{
			$table->string('STEP_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('0');
			$table->string('TAS_UID', 32)->default('0');
			$table->string('STEP_TYPE_OBJ', 20)->default('DYNAFORM');
			$table->string('STEP_UID_OBJ', 32)->default('0');
			$table->text('STEP_CONDITION', 16777215);
			$table->integer('STEP_POSITION')->default(0);
			$table->string('STEP_MODE', 10)->nullable()->default('EDIT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('STEP');
	}

}
