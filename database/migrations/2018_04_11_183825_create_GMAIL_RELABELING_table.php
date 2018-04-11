<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGMAILRELABELINGTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('GMAIL_RELABELING', function(Blueprint $table)
		{
			$table->string('LABELING_UID', 32)->primary();
			$table->dateTime('CREATE_DATE');
			$table->string('APP_UID', 32)->default('');
			$table->integer('DEL_INDEX')->default(0);
			$table->integer('CURRENT_LAST_INDEX')->default(0);
			$table->integer('UNASSIGNED')->default(0);
			$table->string('STATUS', 32)->default('pending')->index('indexStatus');
			$table->text('MSG_ERROR', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('GMAIL_RELABELING');
	}

}
