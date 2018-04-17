<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPTHREADTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_THREAD', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->integer('APP_THREAD_INDEX')->default(0);
			$table->integer('APP_THREAD_PARENT')->default(0);
			$table->string('APP_THREAD_STATUS', 32)->default('OPEN');
			$table->integer('DEL_INDEX')->default(0);
			$table->primary(['APP_UID','APP_THREAD_INDEX']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_THREAD');
	}

}
