<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNARTIFACTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_ARTIFACT', function(Blueprint $table)
		{
			$table->string('ART_UID', 32)->default('')->index('BPMN_ARTIFACT_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_ARTIFACT_I_2');
			$table->string('PRO_UID', 32)->nullable()->default('')->index('BPMN_ARTIFACT_I_3');
			$table->string('ART_TYPE', 15);
			$table->text('ART_NAME', 16777215)->nullable();
			$table->string('ART_CATEGORY_REF', 32)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_ARTIFACT');
	}

}
