<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLOGINLOGTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LOGIN_LOG', function(Blueprint $table)
		{
			$table->integer('LOG_ID', true);
			$table->string('LOG_UID', 32)->default('');
			$table->string('LOG_STATUS', 100)->default('');
			$table->string('LOG_IP', 15)->default('');
			$table->string('LOG_SID', 100)->default('');
			$table->dateTime('LOG_INIT_DATE')->nullable();
			$table->dateTime('LOG_END_DATE')->nullable();
			$table->string('LOG_CLIENT_HOSTNAME', 100)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->index(['LOG_SID','USR_UID','LOG_STATUS','LOG_END_DATE'], 'indexLoginLogSelect');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LOGIN_LOG');
	}

}
