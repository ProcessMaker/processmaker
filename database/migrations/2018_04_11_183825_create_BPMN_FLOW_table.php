<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNFLOWTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_FLOW', function(Blueprint $table)
		{
			$table->string('FLO_UID', 32)->default('')->index('BPMN_FLOW_I_1');
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_FLOW_I_2');
			$table->string('DIA_UID', 32)->default('')->index('BPMN_FLOW_I_3');
			$table->string('FLO_TYPE', 20)->default('');
			$table->string('FLO_NAME')->nullable()->default('');
			$table->string('FLO_ELEMENT_ORIGIN', 32)->default('');
			$table->string('FLO_ELEMENT_ORIGIN_TYPE', 32)->default('');
			$table->integer('FLO_ELEMENT_ORIGIN_PORT')->default(0);
			$table->string('FLO_ELEMENT_DEST', 32)->default('');
			$table->string('FLO_ELEMENT_DEST_TYPE', 32)->default('');
			$table->integer('FLO_ELEMENT_DEST_PORT')->default(0);
			$table->boolean('FLO_IS_INMEDIATE')->nullable();
			$table->string('FLO_CONDITION', 512)->nullable();
			$table->integer('FLO_X1')->default(0);
			$table->integer('FLO_Y1')->default(0);
			$table->integer('FLO_X2')->default(0);
			$table->integer('FLO_Y2')->default(0);
			$table->text('FLO_STATE', 16777215)->nullable();
			$table->integer('FLO_POSITION')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_FLOW');
	}

}
