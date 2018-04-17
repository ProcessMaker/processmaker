<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSCATEGORYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PROCESS_CATEGORY', function(Blueprint $table)
		{
			$table->integer('CATEGORY_ID', true);
			$table->string('CATEGORY_UID', 32)->default('')->unique('UQ_CATEGORY_UID');
			$table->string('CATEGORY_NAME', 100)->default('');
			$table->dateTime('CREATED_AT')->nullable();
			$table->dateTime('UPDATED_AT')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PROCESS_CATEGORY');
	}

}
