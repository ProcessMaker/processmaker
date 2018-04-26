<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOBJECTPERMISSIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('OBJECT_PERMISSION', function(Blueprint $table)
		{
			$table->string('OP_UID', 32)->default('0')->primary();
			$table->string('PRO_UID', 32)->default('0');
			$table->string('TAS_UID', 32)->default('0');
			$table->string('USR_UID', 32)->default('0');
			$table->integer('OP_USER_RELATION')->default(0);
			$table->string('OP_TASK_SOURCE', 32)->nullable()->default('0');
			$table->integer('OP_PARTICIPATE')->default(0);
			$table->string('OP_OBJ_TYPE', 15)->default('0');
			$table->string('OP_OBJ_UID', 32)->default('0');
			$table->string('OP_ACTION', 10)->default('0');
			$table->string('OP_CASE_STATUS', 10)->nullable()->default('0');
			$table->index(['PRO_UID','TAS_UID','USR_UID','OP_TASK_SOURCE','OP_OBJ_UID'], 'indexObjctPermission');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('OBJECT_PERMISSION');
	}

}
