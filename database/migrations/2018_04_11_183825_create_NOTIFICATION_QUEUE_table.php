<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNOTIFICATIONQUEUETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('NOTIFICATION_QUEUE', function(Blueprint $table)
		{
			$table->string('NOT_UID', 32)->primary();
			$table->string('DEV_TYPE', 50);
			$table->text('DEV_UID', 16777215);
			$table->text('NOT_MSG', 16777215);
			$table->text('NOT_DATA', 16777215);
			$table->string('NOT_STATUS', 150)->index('indexNotStatus');
			$table->dateTime('NOT_SEND_DATE');
			$table->string('APP_UID', 32)->default('');
			$table->integer('DEL_INDEX')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('NOTIFICATION_QUEUE');
	}

}
