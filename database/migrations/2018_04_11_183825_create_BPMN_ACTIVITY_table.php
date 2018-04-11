<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNACTIVITYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_ACTIVITY', function(Blueprint $table)
		{
			$table->string('ACT_UID', 32)->default('')->index('BPMN_ACTIVITY_I_1');
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_ACTIVITY_I_2');
			$table->string('PRO_UID', 32)->nullable()->default('')->index('BPMN_ACTIVITY_I_3');
			$table->string('ACT_NAME');
			$table->string('ACT_TYPE', 30)->default('TASK');
			$table->boolean('ACT_IS_FOR_COMPENSATION')->nullable()->default(0);
			$table->integer('ACT_START_QUANTITY')->nullable()->default(1);
			$table->integer('ACT_COMPLETION_QUANTITY')->nullable()->default(1);
			$table->string('ACT_TASK_TYPE', 20)->default('EMPTY');
			$table->text('ACT_IMPLEMENTATION', 16777215)->nullable();
			$table->boolean('ACT_INSTANTIATE')->nullable()->default(0);
			$table->string('ACT_SCRIPT_TYPE')->nullable();
			$table->text('ACT_SCRIPT', 16777215)->nullable();
			$table->string('ACT_LOOP_TYPE', 20)->default('NONE');
			$table->boolean('ACT_TEST_BEFORE')->nullable()->default(0);
			$table->integer('ACT_LOOP_MAXIMUM')->nullable()->default(0);
			$table->string('ACT_LOOP_CONDITION', 100)->nullable();
			$table->integer('ACT_LOOP_CARDINALITY')->nullable()->default(0);
			$table->string('ACT_LOOP_BEHAVIOR', 20)->nullable()->default('NONE');
			$table->boolean('ACT_IS_ADHOC')->nullable()->default(0);
			$table->boolean('ACT_IS_COLLAPSED')->nullable()->default(1);
			$table->string('ACT_COMPLETION_CONDITION')->nullable();
			$table->string('ACT_ORDERING', 20)->nullable()->default('PARALLEL');
			$table->boolean('ACT_CANCEL_REMAINING_INSTANCES')->nullable()->default(1);
			$table->string('ACT_PROTOCOL')->nullable();
			$table->string('ACT_METHOD')->nullable();
			$table->boolean('ACT_IS_GLOBAL')->nullable()->default(0);
			$table->string('ACT_REFERER', 32)->nullable()->default('');
			$table->string('ACT_DEFAULT_FLOW', 32)->nullable()->default('');
			$table->string('ACT_MASTER_DIAGRAM', 32)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_ACTIVITY');
	}

}
