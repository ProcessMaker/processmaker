<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNLANETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_LANE', function(Blueprint $table)
		{
			$table->string('LAN_UID', 32)->default('')->index('BPMN_LANE_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_LANE_I_2');
			$table->string('LNS_UID', 32)->index('BPMN_LANE_I_3');
			$table->string('LAN_NAME')->nullable();
			$table->string('LAN_CHILD_LANESET', 32)->nullable();
			$table->boolean('LAN_IS_HORIZONTAL')->nullable()->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_LANE');
	}

}
