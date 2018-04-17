<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDYNAFORMTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DYNAFORM', function(Blueprint $table)
		{
			$table->string('DYN_UID', 32)->default('')->primary();
			$table->text('DYN_TITLE', 16777215);
			$table->text('DYN_DESCRIPTION', 16777215)->nullable();
			$table->string('PRO_UID', 32)->default('0');
			$table->string('DYN_TYPE', 20)->default('xmlform');
			$table->string('DYN_FILENAME', 100)->default('');
			$table->text('DYN_CONTENT', 16777215)->nullable();
			$table->text('DYN_LABEL', 16777215)->nullable();
			$table->integer('DYN_VERSION');
			$table->dateTime('DYN_UPDATE_DATE')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DYNAFORM');
	}

}
