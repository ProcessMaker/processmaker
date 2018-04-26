<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDASHLETTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DASHLET', function(Blueprint $table)
		{
			$table->string('DAS_UID', 32)->default('')->primary();
			$table->string('DAS_CLASS', 50)->default('');
			$table->string('DAS_TITLE')->default('');
			$table->text('DAS_DESCRIPTION', 16777215)->nullable();
			$table->string('DAS_VERSION', 10)->default('1.0');
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
		Schema::drop('DASHLET');
	}

}
