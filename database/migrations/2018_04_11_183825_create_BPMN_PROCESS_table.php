<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNPROCESSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_PROCESS', function(Blueprint $table)
		{
			$table->string('PRO_UID', 32)->default('')->index('BPMN_PROCESS_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_PROCESS_I_2');
			$table->string('DIA_UID', 32)->nullable();
			$table->string('PRO_NAME');
			$table->string('PRO_TYPE', 10)->default('NONE');
			$table->boolean('PRO_IS_EXECUTABLE')->default(0);
			$table->boolean('PRO_IS_CLOSED')->default(0);
			$table->boolean('PRO_IS_SUBPROCESS')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_PROCESS');
	}

}
