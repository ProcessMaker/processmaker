<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToBPMNGATEWAYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BPMN_GATEWAY', function(Blueprint $table)
		{
			$table->foreign('PRO_UID', 'fk_bpmn_gateway_process')->references('PRO_UID')->on('BPMN_PROCESS')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('PRJ_UID', 'fk_bpmn_gateway_project')->references('PRJ_UID')->on('BPMN_PROJECT')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('BPMN_GATEWAY', function(Blueprint $table)
		{
			$table->dropForeign('fk_bpmn_gateway_process');
			$table->dropForeign('fk_bpmn_gateway_project');
		});
	}

}
