<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSUBPROCESSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SUB_PROCESS', function(Blueprint $table)
		{
			$table->string('SP_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->string('PRO_PARENT', 32)->default('');
			$table->string('TAS_PARENT', 32)->default('');
			$table->string('SP_TYPE', 20)->default('');
			$table->integer('SP_SYNCHRONOUS')->default(0);
			$table->string('SP_SYNCHRONOUS_TYPE', 20)->default('');
			$table->integer('SP_SYNCHRONOUS_WAIT')->default(0);
			$table->text('SP_VARIABLES_OUT', 16777215);
			$table->text('SP_VARIABLES_IN', 16777215)->nullable();
			$table->string('SP_GRID_IN', 50)->default('');
			$table->index(['PRO_UID','PRO_PARENT'], 'indexSubProcess');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SUB_PROCESS');
	}

}
