<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCASETRACKEROBJECTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CASE_TRACKER_OBJECT', function(Blueprint $table)
		{
			$table->string('CTO_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('0');
			$table->string('CTO_TYPE_OBJ', 20)->default('DYNAFORM');
			$table->string('CTO_UID_OBJ', 32)->default('0');
			$table->text('CTO_CONDITION', 16777215);
			$table->integer('CTO_POSITION')->default(0);
			$table->index(['PRO_UID','CTO_UID_OBJ'], 'indexCaseTrackerObject');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CASE_TRACKER_OBJECT');
	}

}
