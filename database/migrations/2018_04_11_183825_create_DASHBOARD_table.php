<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDASHBOARDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DASHBOARD', function(Blueprint $table)
		{
			$table->string('DAS_UID', 32)->default('')->primary();
			$table->string('DAS_TITLE')->default('');
			$table->text('DAS_DESCRIPTION', 16777215)->nullable();
			$table->dateTime('DAS_CREATE_DATE');
			$table->dateTime('DAS_UPDATE_DATE')->nullable();
			$table->boolean('DAS_STATUS')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DASHBOARD');
	}

}
