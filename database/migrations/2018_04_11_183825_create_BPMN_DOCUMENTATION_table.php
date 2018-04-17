<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNDOCUMENTATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_DOCUMENTATION', function(Blueprint $table)
		{
			$table->string('DOC_UID', 32)->index('BPMN_DOCUMENTATION_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_DOCUMENTATION_I_2');
			$table->string('DOC_ELEMENT', 32);
			$table->string('DOC_ELEMENT_TYPE', 45);
			$table->text('DOC_DOCUMENTATION', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_DOCUMENTATION');
	}

}
