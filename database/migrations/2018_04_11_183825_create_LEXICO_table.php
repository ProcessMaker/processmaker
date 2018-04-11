<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLEXICOTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LEXICO', function(Blueprint $table)
		{
			$table->string('LEX_TOPIC', 64)->default('');
			$table->string('LEX_KEY', 128)->default('');
			$table->string('LEX_VALUE', 128)->default('');
			$table->string('LEX_CAPTION', 128)->default('');
			$table->primary(['LEX_TOPIC','LEX_KEY']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LEXICO');
	}

}
