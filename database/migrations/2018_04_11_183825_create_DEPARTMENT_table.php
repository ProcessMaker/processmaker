<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDEPARTMENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DEPARTMENT', function(Blueprint $table)
		{
			$table->string('DEP_UID', 32)->default('')->primary();
			$table->text('DEP_TITLE', 16777215);
			$table->string('DEP_PARENT', 32)->default('')->index('DEP_BYPARENT');
			$table->string('DEP_MANAGER', 32)->default('');
			$table->integer('DEP_LOCATION')->default(0);
			$table->string('DEP_STATUS', 10)->default('ACTIVE');
			$table->string('DEP_REF_CODE', 50)->default('');
			$table->string('DEP_LDAP_DN')->default('')->index('BY_DEP_LDAP_DN');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DEPARTMENT');
	}

}
