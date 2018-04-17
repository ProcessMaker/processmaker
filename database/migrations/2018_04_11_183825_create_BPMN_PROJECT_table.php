<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNPROJECTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_PROJECT', function(Blueprint $table)
		{
			$table->integer('PRJ_ID', true);
			$table->string('PRJ_UID', 32)->default('')->index('BPMN_PROJECT_I_1');
			$table->string('PRJ_NAME')->default('');
			$table->string('PRJ_DESCRIPTION', 512)->nullable();
			$table->text('PRJ_TARGET_NAMESPACE', 16777215)->nullable();
			$table->text('PRJ_EXPRESION_LANGUAGE', 16777215)->nullable();
			$table->text('PRJ_TYPE_LANGUAGE', 16777215)->nullable();
			$table->text('PRJ_EXPORTER', 16777215)->nullable();
			$table->text('PRJ_EXPORTER_VERSION', 16777215)->nullable();
			$table->dateTime('PRJ_CREATE_DATE');
			$table->dateTime('PRJ_UPDATE_DATE')->nullable();
			$table->text('PRJ_AUTHOR', 16777215)->nullable();
			$table->text('PRJ_AUTHOR_VERSION', 16777215)->nullable();
			$table->text('PRJ_ORIGINAL_SOURCE', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_PROJECT');
	}

}
