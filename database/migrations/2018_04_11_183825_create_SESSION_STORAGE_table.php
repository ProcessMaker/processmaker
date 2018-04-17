<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSESSIONSTORAGETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SESSION_STORAGE', function(Blueprint $table)
		{
			$table->string('ID', 128)->index('indexSessionStorage');
			$table->string('SET_TIME', 10);
			$table->text('DATA', 16777215);
			$table->string('SESSION_KEY', 128);
			$table->string('CLIENT_ADDRESS', 32)->nullable()->default('0.0.0.0');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SESSION_STORAGE');
	}

}
