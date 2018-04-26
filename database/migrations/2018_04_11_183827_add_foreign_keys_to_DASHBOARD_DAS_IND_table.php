<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDASHBOARDDASINDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('DASHBOARD_DAS_IND', function(Blueprint $table)
		{
			$table->foreign('DAS_UID', 'fk_dashboard_indicator_dashboard_das_ind')->references('DAS_UID')->on('DASHBOARD')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('DASHBOARD_DAS_IND', function(Blueprint $table)
		{
			$table->dropForeign('fk_dashboard_indicator_dashboard_das_ind');
		});
	}

}
