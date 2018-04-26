<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNEXTENSIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_EXTENSION', function(Blueprint $table)
		{
			$table->string('EXT_UID', 32)->index('BPMN_EXTENSION_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_EXTENSION_I_2');
			$table->string('EXT_ELEMENT', 32);
			$table->string('EXT_ELEMENT_TYPE', 45);
			$table->text('EXT_EXTENSION', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_EXTENSION');
	}

}
