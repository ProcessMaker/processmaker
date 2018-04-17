<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSTAGETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('STAGE', function(Blueprint $table)
		{
			$table->string('STG_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->integer('STG_POSX')->default(0);
			$table->integer('STG_POSY')->default(0);
			$table->integer('STG_INDEX')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('STAGE');
	}

}
