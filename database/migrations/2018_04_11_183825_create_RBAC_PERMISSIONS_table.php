<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACPERMISSIONSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_PERMISSIONS', function(Blueprint $table)
		{
			$table->string('PER_UID', 32)->default('')->primary();
			$table->string('PER_CODE', 64)->default('')->index('indexPermissionsCode');
			$table->dateTime('PER_CREATE_DATE')->nullable();
			$table->dateTime('PER_UPDATE_DATE')->nullable();
			$table->integer('PER_STATUS')->default(1);
			$table->string('PER_SYSTEM', 32)->default('00000000000000000000000000000002');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_PERMISSIONS');
	}

}
