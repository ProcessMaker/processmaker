<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACROLESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_ROLES', function(Blueprint $table)
		{
			$table->string('ROL_UID', 32)->default('')->primary();
			$table->string('ROL_PARENT', 32)->default('');
			$table->string('ROL_SYSTEM', 32)->default('');
			$table->string('ROL_CODE', 32)->default('');
			$table->dateTime('ROL_CREATE_DATE')->nullable();
			$table->dateTime('ROL_UPDATE_DATE')->nullable();
			$table->integer('ROL_STATUS')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_ROLES');
	}

}
