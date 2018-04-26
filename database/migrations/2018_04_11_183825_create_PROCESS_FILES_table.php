<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSFILESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PROCESS_FILES', function(Blueprint $table)
		{
			$table->integer('PRF_ID', true);
			$table->string('PRF_UID', 32);
			$table->string('PRO_UID', 32);
			$table->string('USR_UID', 32);
			$table->string('PRF_UPDATE_USR_UID', 32);
			$table->string('PRF_PATH', 256)->default('');
			$table->string('PRF_TYPE', 32)->nullable()->default('');
			$table->boolean('PRF_EDITABLE')->nullable()->default(1);
			$table->string('PRF_DRIVE', 32);
			$table->string('PRF_PATH_FOR_CLIENT');
			$table->dateTime('PRF_CREATE_DATE');
			$table->dateTime('PRF_UPDATE_DATE')->nullable();
			$table->unique(['PRO_UID','PRF_PATH_FOR_CLIENT'], 'UQ_PRO_UID_PRF_PATH_FOR_CLIENT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PROCESS_FILES');
	}

}
