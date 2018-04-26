<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLICENSEMANAGERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LICENSE_MANAGER', function(Blueprint $table)
		{
			$table->string('LICENSE_UID', 32)->primary();
			$table->string('LICENSE_USER', 150)->default('0');
			$table->integer('LICENSE_START')->default(0);
			$table->integer('LICENSE_END')->default(0);
			$table->integer('LICENSE_SPAN')->default(0);
			$table->string('LICENSE_STATUS', 100)->default('');
			$table->text('LICENSE_DATA', 16777215);
			$table->string('LICENSE_PATH')->default('0');
			$table->string('LICENSE_WORKSPACE', 32)->default('0');
			$table->string('LICENSE_TYPE', 32)->default('0');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LICENSE_MANAGER');
	}

}
