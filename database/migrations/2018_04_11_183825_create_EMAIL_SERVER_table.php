<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEMAILSERVERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('EMAIL_SERVER', function(Blueprint $table)
		{
			$table->string('MESS_UID', 32)->default('')->primary();
			$table->string('MESS_ENGINE', 256)->default('');
			$table->string('MESS_SERVER', 256)->default('');
			$table->integer('MESS_PORT')->default(0);
			$table->integer('MESS_RAUTH')->default(0);
			$table->string('MESS_ACCOUNT', 256)->default('');
			$table->string('MESS_PASSWORD', 256)->default('');
			$table->string('MESS_FROM_MAIL', 256)->nullable()->default('');
			$table->string('MESS_FROM_NAME', 256)->nullable()->default('');
			$table->string('SMTPSECURE', 3)->default('No');
			$table->integer('MESS_TRY_SEND_INMEDIATLY')->default(0);
			$table->string('MAIL_TO', 256)->nullable()->default('');
			$table->integer('MESS_DEFAULT')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('EMAIL_SERVER');
	}

}
