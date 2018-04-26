<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPHISTORYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_HISTORY', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->integer('DEL_INDEX')->default(0);
			$table->string('PRO_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->string('DYN_UID', 32)->default('')->index('indexDynUid');
			$table->string('OBJ_TYPE', 20)->default('DYNAFORM');
			$table->string('USR_UID', 32)->default('');
			$table->string('APP_STATUS', 100)->default('');
			$table->dateTime('HISTORY_DATE')->nullable();
			$table->text('HISTORY_DATA', 16777215);
			$table->index(['APP_UID','TAS_UID','USR_UID'], 'indexAppHistory');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_HISTORY');
	}

}
