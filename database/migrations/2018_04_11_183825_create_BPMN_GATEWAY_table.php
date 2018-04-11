<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNGATEWAYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_GATEWAY', function(Blueprint $table)
		{
			$table->string('GAT_UID', 32)->default('')->index('BPMN_GATEWAY_I_1');
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_GATEWAY_I_2');
			$table->string('PRO_UID', 32)->nullable()->default('')->index('BPMN_GATEWAY_I_3');
			$table->string('GAT_NAME')->nullable();
			$table->string('GAT_TYPE', 30)->default('');
			$table->string('GAT_DIRECTION', 30)->nullable()->default('UNSPECIFIED');
			$table->boolean('GAT_INSTANTIATE')->nullable()->default(0);
			$table->string('GAT_EVENT_GATEWAY_TYPE', 20)->nullable()->default('NONE');
			$table->integer('GAT_ACTIVATION_COUNT')->nullable()->default(0);
			$table->boolean('GAT_WAITING_FOR_START')->nullable()->default(1);
			$table->string('GAT_DEFAULT_FLOW', 32)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_GATEWAY');
	}

}
