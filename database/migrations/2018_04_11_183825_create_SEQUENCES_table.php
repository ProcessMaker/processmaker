<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSEQUENCESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SEQUENCES', function(Blueprint $table)
		{
			$table->string('SEQ_NAME', 50)->default('')->primary();
			$table->integer('SEQ_VALUE')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SEQUENCES');
	}

}
