<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACUSERSROLESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_USERS_ROLES', function(Blueprint $table)
		{
			$table->string('USR_UID', 32)->default('');
			$table->string('ROL_UID', 32)->default('');
			$table->primary(['USR_UID','ROL_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_USERS_ROLES');
	}

}
