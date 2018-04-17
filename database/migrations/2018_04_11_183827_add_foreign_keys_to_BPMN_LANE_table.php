<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToBPMNLANETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BPMN_LANE', function(Blueprint $table)
		{
			$table->foreign('PRJ_UID', 'fk_bpmn_lane_project')->references('PRJ_UID')->on('BPMN_PROJECT')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('BPMN_LANE', function(Blueprint $table)
		{
			$table->dropForeign('fk_bpmn_lane_project');
		});
	}

}
