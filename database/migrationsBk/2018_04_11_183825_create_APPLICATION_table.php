<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPLICATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APPLICATION', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->text('APP_TITLE', 16777215);
			$table->text('APP_DESCRIPTION', 16777215)->nullable();
			$table->string('APP_PARENT', 32)->default('0');
			$table->string('APP_STATUS', 100)->default('')->index('indexAppStatus');
			$table->boolean('APP_STATUS_ID')->default(0);
			$table->unsignedInteger('process_id');
			$table->string('APP_PROC_STATUS', 100)->default('');
			$table->string('APP_PROC_CODE', 100)->default('');
			$table->string('APP_PARALLEL', 32)->default('NO');
			$table->unsignedInteger('creator_user_id');
			$table->unsignedInteger('current_user_id')->nullable();
			$table->dateTime('APP_CREATE_DATE')->index('indexAppCreateDate');
			$table->dateTime('APP_INIT_DATE');
			$table->dateTime('APP_FINISH_DATE')->nullable();
			$table->dateTime('APP_UPDATE_DATE');
			$table->text('APP_DATA');
			$table->string('APP_PIN', 256)->default('');
			$table->float('APP_DURATION', 10, 0)->nullable()->default(0);
			$table->float('APP_DELAY_DURATION', 10, 0)->nullable()->default(0);
			$table->string('APP_DRIVE_FOLDER_UID', 32)->nullable()->default('');
			$table->text('APP_ROUTING_DATA', 16777215)->nullable();
			$table->index(['process_id','APP_STATUS'], 'processStatusIdx');

		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APPLICATION');
	}

}
