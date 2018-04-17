<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNDIAGRAMTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_DIAGRAM', function(Blueprint $table)
		{
			$table->string('DIA_UID', 32)->default('')->index('BPMN_DIAGRAM_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_DIAGRAM_I_2');
			$table->string('DIA_NAME')->nullable();
			$table->boolean('DIA_IS_CLOSABLE')->nullable()->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_DIAGRAM');
	}

}
