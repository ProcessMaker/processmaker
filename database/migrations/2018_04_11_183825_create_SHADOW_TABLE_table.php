<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSHADOWTABLETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SHADOW_TABLE', function(Blueprint $table)
		{
			$table->string('SHD_UID', 32)->default('')->index('indexShadowTable');
			$table->string('ADD_TAB_UID', 32)->default('');
			$table->string('SHD_ACTION', 10)->default('');
			$table->text('SHD_DETAILS', 16777215);
			$table->string('USR_UID', 32)->default('');
			$table->string('APP_UID', 32)->default('');
			$table->dateTime('SHD_DATE')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SHADOW_TABLE');
	}

}
