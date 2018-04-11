<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTRIGGERSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('TRIGGERS', function(Blueprint $table)
		{
			$table->string('TRI_UID', 32)->default('')->primary();
			$table->text('TRI_TITLE', 16777215);
			$table->text('TRI_DESCRIPTION', 16777215)->nullable();
			$table->string('PRO_UID', 32)->default('');
			$table->string('TRI_TYPE', 20)->default('SCRIPT');
			$table->text('TRI_WEBBOT', 16777215);
			$table->text('TRI_PARAM', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('TRIGGERS');
	}

}
