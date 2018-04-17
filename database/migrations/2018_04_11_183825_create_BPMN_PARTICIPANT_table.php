<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNPARTICIPANTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_PARTICIPANT', function(Blueprint $table)
		{
			$table->string('PAR_UID', 32)->default('')->index('BPMN_PARTICIPANT_I_1');
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_PARTICIPANT_I_2');
			$table->string('PRO_UID', 32)->nullable()->default('');
			$table->string('LNS_UID', 32)->nullable()->default('');
			$table->string('PAR_NAME')->default('');
			$table->integer('PAR_MINIMUM')->nullable()->default(0);
			$table->integer('PAR_MAXIMUM')->nullable()->default(1);
			$table->integer('PAR_NUM_PARTICIPANTS')->nullable()->default(1);
			$table->boolean('PAR_IS_HORIZONTAL')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_PARTICIPANT');
	}

}
