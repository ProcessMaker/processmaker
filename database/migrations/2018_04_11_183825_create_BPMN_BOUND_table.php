<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNBOUNDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_BOUND', function(Blueprint $table)
		{
			$table->string('BOU_UID', 32)->default('')->index('BPMN_BOUND_I_1');
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_BOUND_I_2');
			$table->string('DIA_UID', 32)->default('')->index('BPMN_BOUND_I_3');
			$table->string('ELEMENT_UID', 32)->nullable()->default('');
			$table->string('BOU_ELEMENT', 32)->default('');
			$table->string('BOU_ELEMENT_TYPE', 32)->default('');
			$table->integer('BOU_X')->default(0);
			$table->integer('BOU_Y')->default(0);
			$table->integer('BOU_WIDTH')->default(0);
			$table->integer('BOU_HEIGHT')->default(0);
			$table->integer('BOU_REL_POSITION')->nullable()->default(0);
			$table->integer('BOU_SIZE_IDENTICAL')->nullable()->default(0);
			$table->string('BOU_CONTAINER', 30)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_BOUND');
	}

}
