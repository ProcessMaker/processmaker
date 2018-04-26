<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUSERSPROPERTIESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('USERS_PROPERTIES', function(Blueprint $table)
		{
			$table->string('USR_UID', 32)->default('')->primary();
			$table->dateTime('USR_LAST_UPDATE_DATE')->nullable();
			$table->integer('USR_LOGGED_NEXT_TIME')->nullable()->default(0);
			$table->text('USR_PASSWORD_HISTORY', 16777215)->nullable();
			$table->text('USR_SETTING_DESIGNER', 16777215)->nullable();
			$table->char('PMDYNAFORM_FIRST_TIME', 1)->nullable()->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('USERS_PROPERTIES');
	}

}
