<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPEVENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_EVENT', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->integer('DEL_INDEX')->default(0);
			$table->string('EVN_UID', 32)->default('');
			$table->dateTime('APP_EVN_ACTION_DATE');
			$table->boolean('APP_EVN_ATTEMPTS')->default(0);
			$table->dateTime('APP_EVN_LAST_EXECUTION_DATE')->nullable();
			$table->string('APP_EVN_STATUS', 32)->default('OPEN');
			$table->primary(['APP_UID','DEL_INDEX','EVN_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_EVENT');
	}

}
