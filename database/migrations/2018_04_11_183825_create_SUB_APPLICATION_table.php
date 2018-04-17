<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSUBAPPLICATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SUB_APPLICATION', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->string('APP_PARENT', 32)->default('')->index('indexParent');
			$table->integer('DEL_INDEX_PARENT')->default(0);
			$table->integer('DEL_THREAD_PARENT')->default(0);
			$table->string('SA_STATUS', 32)->default('');
			$table->text('SA_VALUES_OUT', 16777215);
			$table->text('SA_VALUES_IN', 16777215)->nullable();
			$table->dateTime('SA_INIT_DATE')->nullable();
			$table->dateTime('SA_FINISH_DATE')->nullable();
			$table->primary(['APP_UID','APP_PARENT','DEL_INDEX_PARENT','DEL_THREAD_PARENT'], 'primaryKey');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SUB_APPLICATION');
	}

}
