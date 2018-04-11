<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSVARIABLESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PROCESS_VARIABLES', function(Blueprint $table)
		{
			$table->string('VAR_UID', 32)->unique();
			$table->integer('VAR_ID', true);
			$table->integer('PRO_ID')->nullable();
			$table->string('VAR_NAME')->nullable()->default('');
			$table->string('VAR_FIELD_TYPE', 32)->nullable()->default('');
			$table->integer('VAR_FIELD_SIZE')->nullable();
			$table->string('VAR_LABEL')->nullable()->default('');
			$table->string('VAR_DBCONNECTION', 32)->nullable()->default('workflow');
			$table->text('VAR_SQL', 16777215)->nullable();
			$table->boolean('VAR_NULL')->nullable()->default(0);
			$table->string('VAR_DEFAULT', 32)->nullable()->default('');
			$table->text('VAR_ACCEPTED_VALUES', 16777215)->nullable();
			$table->string('INP_DOC_UID', 32)->nullable()->default('');
			$table->unique(['PRO_ID','VAR_NAME'], 'uniqueVariableName');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PROCESS_VARIABLES');
	}

}
