<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNEVENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_EVENT', function(Blueprint $table)
		{
			$table->string('EVN_UID', 32)->default('')->index('BPMN_EVENT_I_1');
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_EVENT_I_2');
			$table->string('PRO_UID', 32)->nullable()->default('')->index('BPMN_EVENT_I_3');
			$table->string('EVN_NAME')->nullable();
			$table->string('EVN_TYPE', 30)->default('');
			$table->string('EVN_MARKER', 30)->default('EMPTY');
			$table->boolean('EVN_IS_INTERRUPTING')->nullable()->default(1);
			$table->string('EVN_ATTACHED_TO', 32)->nullable()->default('');
			$table->boolean('EVN_CANCEL_ACTIVITY')->nullable()->default(0);
			$table->string('EVN_ACTIVITY_REF', 32)->nullable()->default('');
			$table->boolean('EVN_WAIT_FOR_COMPLETION')->nullable()->default(1);
			$table->string('EVN_ERROR_NAME')->nullable();
			$table->string('EVN_ERROR_CODE')->nullable();
			$table->string('EVN_ESCALATION_NAME')->nullable();
			$table->string('EVN_ESCALATION_CODE')->nullable();
			$table->string('EVN_CONDITION')->nullable();
			$table->text('EVN_MESSAGE', 16777215)->nullable();
			$table->string('EVN_OPERATION_NAME')->nullable();
			$table->string('EVN_OPERATION_IMPLEMENTATION_REF')->nullable();
			$table->string('EVN_TIME_DATE')->nullable();
			$table->string('EVN_TIME_CYCLE')->nullable();
			$table->string('EVN_TIME_DURATION')->nullable();
			$table->string('EVN_BEHAVIOR', 20)->default('CATCH');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_EVENT');
	}

}
