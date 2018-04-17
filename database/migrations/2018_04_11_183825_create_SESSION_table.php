<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSESSIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SESSION', function(Blueprint $table)
		{
			$table->string('SES_UID', 32)->default('')->index('indexSession');
			$table->string('SES_STATUS', 16)->default('ACTIVE');
			$table->string('USR_UID', 32)->default('ACTIVE');
			$table->string('SES_REMOTE_IP', 32)->default('0.0.0.0');
			$table->string('SES_INIT_DATE', 19)->default('');
			$table->string('SES_DUE_DATE', 19)->default('');
			$table->string('SES_END_DATE', 19)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SESSION');
	}

}
