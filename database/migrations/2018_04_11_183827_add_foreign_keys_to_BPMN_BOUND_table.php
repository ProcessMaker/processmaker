<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToBPMNBOUNDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BPMN_BOUND', function(Blueprint $table)
		{
			$table->foreign('DIA_UID', 'fk_bpmn_bound_diagram')->references('DIA_UID')->on('BPMN_DIAGRAM')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('PRJ_UID', 'fk_bpmn_bound_project')->references('PRJ_UID')->on('BPMN_PROJECT')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('BPMN_BOUND', function(Blueprint $table)
		{
			$table->dropForeign('fk_bpmn_bound_diagram');
			$table->dropForeign('fk_bpmn_bound_project');
		});
	}

}
