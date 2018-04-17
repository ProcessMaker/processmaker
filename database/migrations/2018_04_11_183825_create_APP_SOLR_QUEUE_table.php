<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPSOLRQUEUETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_SOLR_QUEUE', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('')->primary();
			$table->dateTime('APP_CHANGE_DATE');
			$table->string('APP_CHANGE_TRACE', 500);
			$table->boolean('APP_UPDATED')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_SOLR_QUEUE');
	}

}
