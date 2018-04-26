<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPFOLDERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_FOLDER', function(Blueprint $table)
		{
			$table->string('FOLDER_UID', 32)->default('')->primary();
			$table->string('FOLDER_PARENT_UID', 32)->default('');
			$table->text('FOLDER_NAME', 16777215);
			$table->dateTime('FOLDER_CREATE_DATE');
			$table->dateTime('FOLDER_UPDATE_DATE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_FOLDER');
	}

}
