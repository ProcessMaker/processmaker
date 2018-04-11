<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACROLESPERMISSIONSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_ROLES_PERMISSIONS', function(Blueprint $table)
		{
			$table->string('ROL_UID', 32)->default('');
			$table->string('PER_UID', 32)->default('');
			$table->primary(['ROL_UID','PER_UID']);
			$table->index(['ROL_UID','PER_UID'], 'indexRolesPermissions');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_ROLES_PERMISSIONS');
	}

}
