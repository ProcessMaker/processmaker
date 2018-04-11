<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDASHLETINSTANCETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DASHLET_INSTANCE', function(Blueprint $table)
		{
			$table->string('DAS_INS_UID', 32)->default('')->primary();
			$table->string('DAS_UID', 32)->default('');
			$table->string('DAS_INS_OWNER_TYPE', 20)->default('');
			$table->string('DAS_INS_OWNER_UID', 32)->nullable()->default('');
			$table->text('DAS_INS_ADDITIONAL_PROPERTIES', 16777215)->nullable();
			$table->dateTime('DAS_INS_CREATE_DATE');
			$table->dateTime('DAS_INS_UPDATE_DATE')->nullable();
			$table->boolean('DAS_INS_STATUS')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DASHLET_INSTANCE');
	}

}
