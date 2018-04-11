<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPOWNERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_OWNER', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->string('OWN_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->primary(['APP_UID','OWN_UID','USR_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_OWNER');
	}

}
