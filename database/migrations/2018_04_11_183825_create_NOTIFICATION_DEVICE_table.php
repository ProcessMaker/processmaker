<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNOTIFICATIONDEVICETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('NOTIFICATION_DEVICE', function(Blueprint $table)
		{
			$table->string('DEV_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('')->index('indexUserNotification');
			$table->string('SYS_LANG', 10)->nullable()->default('');
			$table->string('DEV_REG_ID')->default('');
			$table->string('DEV_TYPE', 50)->default('');
			$table->dateTime('DEV_CREATE');
			$table->dateTime('DEV_UPDATE');
			$table->primary(['DEV_UID','USR_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('NOTIFICATION_DEVICE');
	}

}
