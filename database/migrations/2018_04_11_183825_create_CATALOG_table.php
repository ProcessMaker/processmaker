<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCATALOGTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CATALOG', function(Blueprint $table)
		{
			$table->string('CAT_UID', 32)->default('');
			$table->string('CAT_LABEL_ID', 100)->default('');
			$table->string('CAT_TYPE', 100)->default('')->index('indexType');
			$table->string('CAT_FLAG', 50)->nullable()->default('');
			$table->text('CAT_OBSERVATION', 16777215)->nullable();
			$table->dateTime('CAT_CREATE_DATE');
			$table->dateTime('CAT_UPDATE_DATE')->nullable();
			$table->primary(['CAT_UID','CAT_TYPE']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CATALOG');
	}

}
