<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateADDITIONALTABLESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ADDITIONAL_TABLES', function(Blueprint $table)
		{
			$table->string('ADD_TAB_UID', 32)->default('');
			$table->integer('ADD_TAB_ID', true);
			$table->string('ADD_TAB_NAME', 60)->default('');
			$table->text('ADD_TAB_DESCRIPTION', 16777215)->nullable();
			$table->string('ADD_TAB_PLG_UID', 32)->nullable()->default('');
			$table->string('DBS_UID', 32)->nullable()->default('');
			$table->string('PRO_UID', 32)->nullable()->default('')->index('indexAdditionalProcess');
			$table->integer('PRO_ID')->nullable();
			$table->string('ADD_TAB_TYPE', 32)->nullable()->default('');
			$table->string('ADD_TAB_GRID', 256)->nullable()->default('');
			$table->string('ADD_TAB_TAG', 256)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ADDITIONAL_TABLES');
	}

}
