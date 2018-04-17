<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACUSERSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_USERS', function(Blueprint $table)
		{
			$table->string('USR_UID', 32)->default('')->primary();
			$table->string('USR_USERNAME', 100)->default('');
			$table->string('USR_PASSWORD', 128)->default('');
			$table->string('USR_FIRSTNAME', 50)->default('');
			$table->string('USR_LASTNAME', 50)->default('');
			$table->string('USR_EMAIL', 100)->default('');
			$table->date('USR_DUE_DATE');
			$table->dateTime('USR_CREATE_DATE')->nullable();
			$table->dateTime('USR_UPDATE_DATE')->nullable();
			$table->integer('USR_STATUS')->default(1);
			$table->string('USR_AUTH_TYPE', 32)->default('');
			$table->string('UID_AUTH_SOURCE', 32)->default('');
			$table->string('USR_AUTH_USER_DN')->default('');
			$table->string('USR_AUTH_SUPERVISOR_DN')->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_USERS');
	}

}
