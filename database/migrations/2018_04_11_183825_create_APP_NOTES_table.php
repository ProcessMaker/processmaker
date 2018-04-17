<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPNOTESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_NOTES', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->dateTime('NOTE_DATE');
			$table->text('NOTE_CONTENT', 16777215);
			$table->string('NOTE_TYPE', 32)->default('USER');
			$table->string('NOTE_AVAILABILITY', 32)->default('PUBLIC');
			$table->string('NOTE_ORIGIN_OBJ', 32)->nullable()->default('');
			$table->string('NOTE_AFFECTED_OBJ1', 32)->nullable()->default('');
			$table->string('NOTE_AFFECTED_OBJ2', 32)->default('');
			$table->text('NOTE_RECIPIENTS', 16777215)->nullable();
			$table->index(['APP_UID','NOTE_DATE'], 'indexAppNotesDate');
			$table->index(['APP_UID','USR_UID'], 'indexAppNotesUser');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_NOTES');
	}

}
