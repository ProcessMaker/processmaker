<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACSYSTEMSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_SYSTEMS', function(Blueprint $table)
		{
			$table->string('SYS_UID', 32)->default('')->primary();
			$table->string('SYS_CODE', 32)->default('')->index('indexSystemCode');
			$table->dateTime('SYS_CREATE_DATE')->nullable();
			$table->dateTime('SYS_UPDATE_DATE')->nullable();
			$table->integer('SYS_STATUS')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_SYSTEMS');
	}

}
